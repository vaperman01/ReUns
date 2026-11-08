<?php

function numero_soles(string $s): ?float {
    $s = str_replace(',', '.', trim($s));

    return preg_match('/^\d{1,7}(\.\d{1,2})?$/', $s)
        ? round((float)$s, 2)
        : null;
}

function validar_producto(array $in): array {
    $err = [];

    $t = trim((string)($in['titulo'] ?? ''));
    $d = trim((string)($in['descripcion'] ?? ''));
    $ub = trim((string)($in['ubicacion'] ?? ''));

    if (!preg_match('/^.{5,100}$/u', $t)) {
        $err['titulo'] = 'El título debe tener entre 5 y 100 caracteres.';
    }

    if (!preg_match('/^.{10,2000}$/us', $d)) {
        $err['descripcion'] = 'Describe el producto en 10 a 2000 caracteres.';
    }

    // El formulario envía el ID numérico real de la tabla `categorias`.
    // Si no es un entero positivo que exista en la base, se rechaza aquí
    // (nunca llega al INSERT, así que no puede violar la llave foránea).
    $catId = filter_var(
        $in['categoria_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($catId === false || categoria_por_id($catId) === null) {
        $err['categoria_id'] = 'Elige una categoría.';
        $catId = null;
    }

    $mod = (string)($in['modalidad'] ?? '');

    if (!isset(MODALIDADES[$mod])) {
        $err['modalidad'] = 'Elige la modalidad.';
    }

    $est = (string)($in['estado_conservacion'] ?? '');

    if (!isset(ESTADOS_CONS[$est])) {
        $err['estado_conservacion'] = 'Elige el estado del producto.';
    }

    if (mb_strlen($ub) > 80) {
        $err['ubicacion'] = 'Máximo 80 caracteres.';
    }

    $precio = 0.0;
    $ant = null;

    if (in_array($mod, ['venta', 'ambas'], true)) {
        $precio = numero_soles((string)($in['precio'] ?? ''));

        if ($precio === null || $precio <= 0 || $precio > 1000000) {
            $err['precio'] = 'Escribe un precio entre S/ 0.01 y S/ 1,000,000 (ejemplo: 150 o 150.50).';
        } elseif (trim((string)($in['precio_anterior'] ?? '')) !== '') {
            $ant = numero_soles((string)$in['precio_anterior']);

            if ($ant === null || $ant <= $precio) {
                $err['precio_anterior'] = 'El precio anterior debe ser mayor que el precio actual.';
            }
        }
    }

    return [
        $err,
        [
            'titulo' => $t,
            'descripcion' => $d,
            'categoria_id' => $catId,
            'modalidad' => $mod,
            'estado_conservacion' => $est,
            'ubicacion' => $ub,
            'precio' => $precio,
            'precio_anterior' => $ant
        ]
    ];
}

function guardar_fotos(array $fotos, bool $verificar): array {
    $ok = [];

    foreach ($fotos as $i => $f) {
        [$archivo, $error] = guardar_imagen(
            $f,
            'productos',
            1200,
            false,
            $verificar
        );

        if ($error) {
            foreach ($ok as $guardado) {
                borrar_imagen('productos', $guardado);
            }

            return [[], 'Foto ' . ($i + 1) . ': ' . $error];
        }

        $ok[] = $archivo;
    }

    return [$ok, null];
}

function publicar_producto(
    array $u,
    array $in,
    ?array $files,
    bool $verificar = true
): array {
    if (empty($u['email_verificado_en'])) {
        return [['general' => 'Verifica tu correo para poder publicar.'], null];
    }

    [$err, $d] = validar_producto($in);
    $fotos = normalizar_archivos($files);

    if (!$fotos) {
        $err['fotos'] = 'Sube al menos una foto.';
    } elseif (count($fotos) > MAX_FOTOS) {
        $err['fotos'] = 'Puedes subir máximo ' . MAX_FOTOS . ' fotos.';
    }

    if (publicaciones_recientes((int)$u['id']) >= 10) {
        $err['general'] = 'Publicaste muchos productos en la última hora. Espera un poco.';
    }

    // Si hay cualquier error (incluida la categoría), se devuelve ANTES de
    // guardar las fotos en disco ni tocar la base de datos.
    if ($err) {
        return [$err, null];
    }

    [$archivos, $errorFotos] = guardar_fotos($fotos, $verificar);

    if ($errorFotos) {
        return [['fotos' => $errorFotos], null];
    }

    try {
        db()->beginTransaction();

        $id = crear_producto((int)$u['id'], $d);

        foreach ($archivos as $i => $archivo) {
            agregar_imagen($id, $archivo, $i);
        }

        db()->commit();
    } catch (Throwable $t) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        foreach ($archivos as $archivo) {
            borrar_imagen('productos', $archivo);
        }

        throw $t;
    }

    auditar((int)$u['id'], 'producto_creado', "id=$id");

    return [[], $id];
}

function editar_producto(
    array $u,
    int $id,
    array $in,
    ?array $files,
    array $quitar,
    bool $verificar = true
): array {
    $p = producto_propio($id, (int)$u['id']);

    if (!$p) {
        return ['general' => 'No encontramos esa publicación.'];
    }

    [$err, $d] = validar_producto($in);
    $nuevas = normalizar_archivos($files);

    $quitar = array_map('intval', $quitar);
    $actuales = array_column($p['imagenes'], 'id');

    $quitar = array_values(array_intersect(
        $quitar,
        array_map('intval', $actuales)
    ));

    $total = count($actuales) - count($quitar) + count($nuevas);

    if ($total < 1) {
        $err['fotos'] = 'El producto necesita al menos una foto.';
    } elseif ($total > MAX_FOTOS) {
        $err['fotos'] = 'Máximo ' . MAX_FOTOS . ' fotos en total.';
    }

    if ($err) {
        return $err;
    }

    [$archivos, $errorFotos] = guardar_fotos($nuevas, $verificar);

    if ($errorFotos) {
        return ['fotos' => $errorFotos];
    }

    try {
        db()->beginTransaction();

        actualizar_producto($id, (int)$u['id'], $d);

        foreach ($quitar as $imagenId) {
            $archivo = quitar_imagen($imagenId, $id);

            if ($archivo !== null) {
                borrar_imagen('productos', $archivo);
            }
        }

        $orden = count($actuales) - count($quitar);

        foreach ($archivos as $archivo) {
            agregar_imagen($id, $archivo, $orden++);
        }

        db()->commit();
    } catch (Throwable $t) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        foreach ($archivos as $archivo) {
            borrar_imagen('productos', $archivo);
        }

        throw $t;
    }

    auditar((int)$u['id'], 'producto_editado', "id=$id");

    return [];
}