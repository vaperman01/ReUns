<?php

const ESTADOS_CONS = [
    'nuevo' => 'Nuevo',
    'como_nuevo' => 'Usado - Como nuevo',
    'bueno' => 'Usado - Bueno',
    'aceptable' => 'Usado - Aceptable'
];

const MODALIDADES = [
    'venta' => 'Venta',
    'intercambio' => 'Intercambio',
    'ambas' => 'Venta o intercambio'
];

const DISPONIBILIDAD = [
    'activo' => 'Activo',
    'pausado' => 'Pausado',
    'vendido' => 'Vendido',
    'oculto' => 'Oculto',
    'eliminado' => 'Eliminado'
];

const MAX_FOTOS = 5;
function categorias_todas(): array {
    static $categorias = null;
    if ($categorias === null) {
        // Carga las categorías directamente desde la base de datos para mantener los IDs numéricos reales
        $categorias = db()->query("SELECT id, padre_id, slug, nombre, icono FROM categorias ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    }
    return $categorias;
}

function categorias(): array {
    $resultado = [];

    foreach (categorias_todas() as $categoria) {
        $resultado[$categoria['id']] = [
            $categoria['nombre'],
            $categoria['icono']
        ];
    }

    return $resultado;
}

function categoria_por_slug(string $slug): ?array {
    foreach (categorias_todas() as $categoria) {
        if ($categoria['slug'] === $slug) {
            return $categoria;
        }
    }

    return null;
}

// Función auxiliar opcional si necesitas buscar por ID numérico
function categoria_por_id(int $id): ?array {
    foreach (categorias_todas() as $categoria) {
        if ((int)$categoria['id'] === $id) {
            return $categoria;
        }
    }
    return null;
}

/** Devuelve el id dado más los de sus subcategorías (para filtrar por categoría padre). */
function ids_categoria(int $id): array {
    $ids = [$id];

    foreach (categorias_todas() as $categoria) {
        if ($categoria['padre_id'] !== null && (int)$categoria['padre_id'] === $id) {
            $ids[] = (int)$categoria['id'];
        }
    }

    return $ids;
}

/**
 * Categorías listas para un <select> con <optgroup>:
 * [ ['padre' => fila, 'hijas' => [filas...]], ... ]
 */
function categorias_agrupadas(): array {
    $grupos = [];

    foreach (categorias_todas() as $c) {
        if ($c['padre_id'] === null) {
            $grupos[(int)$c['id']] = ['padre' => $c, 'hijas' => []];
        }
    }

    foreach (categorias_todas() as $c) {
        if ($c['padre_id'] !== null && isset($grupos[(int)$c['padre_id']])) {
            $grupos[(int)$c['padre_id']]['hijas'][] = $c;
        }
    }

    return array_values($grupos);
}

/**
 * Convierte el valor recibido en un id de categoría que EXISTE en la base.
 * Lanza excepción en vez de caer en 0 (que rompería la clave foránea).
 */
function categoria_id_valida(mixed $valor): int {
    $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($id === false || categoria_por_id($id) === null) {
        throw new InvalidArgumentException(
            'categoria_id inválido: ' . var_export($valor, true)
        );
    }

    return $id;
}

const SQL_PRODUCTO = "
    SELECT p.*,
           c.slug AS categoria_slug,
           c.nombre AS categoria,
           c.nombre AS categoria_nombre,
           u.nombre AS vendedor,
           u.foto AS vendedor_foto,
           (
               SELECT archivo
               FROM producto_imagenes i
               WHERE i.producto_id = p.id
               ORDER BY i.orden, i.id
               LIMIT 1
           ) AS imagen
";

const SQL_DESDE = "
    FROM productos p
    JOIN usuarios u ON u.id = p.usuario_id
    LEFT JOIN categorias c ON c.id = p.categoria_id
";

function buscar_productos(array $f, int $pag = 1, int $por = 12): array {
    $w = ["p.estado = 'activo'", "u.estado = 'activo'"];
    $pw = [];
    $ps = [];
    $partes = [];

    $palabras = preg_split(
        '/\s+/u',
        trim((string)($f['q'] ?? '')),
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    $palabras = array_slice($palabras ?: [], 0, 5);

    foreach ($palabras as $palabra) {
        $like = '%' . addcslashes($palabra, '%_\\') . '%';

        $w[] = '(p.titulo LIKE ? OR p.descripcion LIKE ?)';
        array_push($pw, $like, $like);

        $partes[] = '((p.titulo LIKE ?)*2 + (p.descripcion LIKE ?))';
        array_push($ps, $like, $like);
    }

    $seleccionRelevancia = $partes
        ? implode(' + ', $partes)
        : '0';

    $categoria = trim((string)($f['cat'] ?? ''));

    if ($categoria !== '') {
        $catData = categoria_por_slug($categoria);
        if ($catData) {
            $idsCat = ids_categoria((int)$catData['id']);
            $w[] = 'p.categoria_id IN (' . implode(',', array_fill(0, count($idsCat), '?')) . ')';
            array_push($pw, ...$idsCat);
        }
    }

    $modalidad = $f['mod'] ?? '';

    if ($modalidad === 'venta') {
        $w[] = "p.modalidad IN ('venta', 'ambas')";
    } elseif ($modalidad === 'intercambio') {
        $w[] = "p.modalidad IN ('intercambio', 'ambas')";
    }

    $estadoProducto = $f['est'] ?? '';

    if (isset(ESTADOS_CONS[$estadoProducto])) {
        $w[] = 'p.estado_producto = ?';
        $pw[] = $estadoProducto;
    }

    if (is_numeric($f['min'] ?? null)) {
        $w[] = 'p.precio >= ?';
        $pw[] = (float)$f['min'];
    }

    if (is_numeric($f['max'] ?? null)) {
        $w[] = 'p.precio <= ?';
        $pw[] = (float)$f['max'];
    }

    $ubicacion = trim((string)($f['ubicacion'] ?? ''));

    if ($ubicacion !== '') {
        $w[] = 'p.ubicacion LIKE ?';
        $pw[] = '%' . addcslashes($ubicacion, '%_\\') . '%';
    }

    if (!empty($f['oferta'])) {
        $w[] = '(p.precio_anterior IS NOT NULL AND p.precio_anterior > p.precio)';
    }

    $orden = $f['orden'] ?? ($palabras ? 'relevancia' : 'recientes');

    $ordenSql = match ($orden) {
        'precio_asc' => 'p.precio ASC, p.id DESC',
        'precio_desc' => 'p.precio DESC, p.id DESC',
        'relevancia' => $palabras
            ? 'relevancia DESC, p.creado_en DESC, p.id DESC'
            : 'p.creado_en DESC, p.id DESC',
        default => 'p.creado_en DESC, p.id DESC'
    };

    $where = implode(' AND ', $w);

    $consultaTotal = db()->prepare(
        "SELECT COUNT(*)
         FROM productos p
         JOIN usuarios u ON u.id = p.usuario_id
         LEFT JOIN categorias c ON c.id = p.categoria_id
         WHERE $where"
    );

    $consultaTotal->execute($pw);
    $total = (int)$consultaTotal->fetchColumn();

    $por = max(1, min(100, $por));
    $paginas = max(1, (int)ceil($total / $por));
    $pag = min(max(1, $pag), $paginas);
    $offset = ($pag - 1) * $por;

    $sql = SQL_PRODUCTO
        . ", ($seleccionRelevancia) AS relevancia"
        . SQL_DESDE
        . " WHERE $where
            ORDER BY $ordenSql
            LIMIT $por OFFSET $offset";

    $consulta = db()->prepare($sql);
    $consulta->execute(array_merge($ps, $pw));

    return [
        'items' => $consulta->fetchAll(),
        'total' => $total,
        'paginas' => $paginas,
        'pag' => $pag
    ];
}

function producto_por_id(int $id): ?array {
    $sql = SQL_PRODUCTO
        . ",
            u.ubicacion AS vendedor_ubicacion,
            u.bio AS vendedor_bio,
            u.creado_en AS vendedor_desde,
            (u.email_verificado_en IS NOT NULL) AS vendedor_verificado,
            u.estado AS vendedor_estado"
        . SQL_DESDE
        . " WHERE p.id = ?
            AND p.estado <> 'eliminado'";

    $consulta = db()->prepare($sql);
    $consulta->execute([$id]);

    $producto = $consulta->fetch();

    if (!$producto) {
        return null;
    }

    $producto['imagenes'] = imagenes_de($id);

    return $producto;
}

function producto_propio(int $id, int $uid): ?array {
    $producto = producto_por_id($id);

    return (
        $producto &&
        (int)$producto['usuario_id'] === $uid
    ) ? $producto : null;
}

function imagenes_de(int $pid): array {
    $consulta = db()->prepare(
        'SELECT *
         FROM producto_imagenes
         WHERE producto_id = ?
         ORDER BY orden, id'
    );

    $consulta->execute([$pid]);

    return $consulta->fetchAll();
}

function crear_producto(int $uid, array $d): int {
    $categoriaId = categoria_id_valida($d['categoria_id'] ?? null);

    $sql = "
        INSERT INTO productos (
            usuario_id,
            titulo,
            descripcion,
            categoria_id,
            estado_producto,
            modalidad,
            precio,
            precio_anterior,
            ubicacion
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    db()->prepare($sql)->execute([
        $uid,
        $d['titulo'],
        $d['descripcion'],
        $categoriaId,
        $d['estado_conservacion'],
        $d['modalidad'],
        $d['precio'] ?? 0,
        $d['precio_anterior'],
        $d['ubicacion'] ?? ''
    ]);

    return (int)db()->lastInsertId();
}

function actualizar_producto(int $id, int $uid, array $d): void {
    $categoriaId = categoria_id_valida($d['categoria_id'] ?? null);

    $sql = "
        UPDATE productos
        SET categoria_id = ?,
            titulo = ?,
            descripcion = ?,
            precio = ?,
            precio_anterior = ?,
            modalidad = ?,
            estado_producto = ?,
            ubicacion = ?
        WHERE id = ?
          AND usuario_id = ?
          AND estado <> 'eliminado'
    ";

    db()->prepare($sql)->execute([
        $categoriaId,
        $d['titulo'],
        $d['descripcion'],
        $d['precio'] ?? 0,
        $d['precio_anterior'],
        $d['modalidad'],
        $d['estado_conservacion'],
        $d['ubicacion'] ?? '',
        $id,
        $uid
    ]);
}

function agregar_imagen(int $pid, string $archivo, int $orden): void {
    db()->prepare(
        'INSERT INTO producto_imagenes (producto_id, archivo, orden)
         VALUES (?, ?, ?)'
    )->execute([$pid, $archivo, $orden]);
}

function quitar_imagen(int $imgId, int $pid): ?string {
    $consulta = db()->prepare(
        'SELECT archivo
         FROM producto_imagenes
         WHERE id = ? AND producto_id = ?'
    );

    $consulta->execute([$imgId, $pid]);
    $archivo = $consulta->fetchColumn();

    if ($archivo === false) {
        return null;
    }

    db()->prepare(
        'DELETE FROM producto_imagenes WHERE id = ?'
    )->execute([$imgId]);

    return $archivo;
}

function cambiar_disponibilidad(
    int $id,
    int $uid,
    string $nuevo
): ?string {
    $producto = producto_propio($id, $uid);

    if (!$producto) {
        return 'No encontramos esa publicación.';
    }

    if (!in_array($nuevo, ['activo', 'pausado', 'vendido', 'oculto', 'eliminado'], true)) {
        return 'Estado no válido.';
    }

    if (
        $nuevo === 'vendido' &&
        $producto['modalidad'] === 'intercambio'
    ) {
        return 'Este producto es solo para intercambio.';
    }

    if (
        $nuevo === 'vendido' &&
        $producto['modalidad'] === 'ambas'
    ) {
        // Se permite marcar como vendido.
    }

    db()->prepare(
        'UPDATE productos
         SET estado = ?
         WHERE id = ? AND usuario_id = ?'
    )->execute([$nuevo, $id, $uid]);

    return null;
}

function productos_de_usuario(int $uid, string $grupo): array {
    if ($grupo === 'historial') {
        $estados = "('vendido')";
    } else {
        $estados = "('activo', 'pausado', 'oculto')";
    }

    $sql = SQL_PRODUCTO
        . SQL_DESDE
        . " WHERE p.usuario_id = ?
            AND p.estado IN $estados
            ORDER BY p.actualizado_en DESC";

    $consulta = db()->prepare($sql);
    $consulta->execute([$uid]);

    return $consulta->fetchAll();
}

function publicaciones_recientes(int $uid): int {
    $consulta = db()->prepare(
        'SELECT COUNT(*)
         FROM productos
         WHERE usuario_id = ?
           AND creado_en > (NOW() - INTERVAL 1 HOUR)'
    );

    $consulta->execute([$uid]);

    return (int)$consulta->fetchColumn();
}

function actualizar_foto_usuario(int $uid, ?string $foto): void {
    db()->prepare(
        'UPDATE usuarios SET foto = ? WHERE id = ?'
    )->execute([$foto, $uid]);
}


/* ---------------------------------------------------------------------
 * Adaptadores para public/paginas/producto.php (la página de detalle).
 * Esa página llama a estas funciones, que no existían en el proyecto, y
 * se rompía justo después de publicar. Reutilizan el modelo PDO de arriba.
 * ------------------------------------------------------------------- */
function obtener_producto_por_id(int $id): ?array {
    $p = producto_por_id($id);

    if (!$p) {
        return null;
    }

    $p['usuario_id'] = (int)$p['usuario_id'];
    $p['categoria_nombre'] = $p['categoria'] ?? null;
    $p['vendedor_nombre'] = $p['vendedor'] ?? null;
    $p['precio_soles'] = (float)($p['precio'] ?? 0);
    $p['permite_intercambio'] = in_array($p['modalidad'], ['intercambio', 'ambas'], true);

    $marca = strtotime((string)($p['creado_en'] ?? ''));
    $p['creado_en_formato'] = $marca ? date('d/m/Y', $marca) : null;

    return $p;
}

function obtener_fotos_producto(int $id): array {
    return array_map(
        fn(array $img): array => ['ruta' => '/uploads/productos/' . $img['archivo']],
        imagenes_de($id)
    );
}

function obtener_mis_productos_activos(int $uid): array {
    $activos = array_filter(
        productos_de_usuario($uid, 'activos'),
        fn(array $p): bool => $p['estado'] === 'activo'
    );

    return array_values(array_map(function (array $p): array {
        $p['precio_soles'] = (float)($p['precio'] ?? 0);
        return $p;
    }, $activos));
}