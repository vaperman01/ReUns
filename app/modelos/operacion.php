<?php
/*
 * ReUns - Etapa 5: ofertas, compras e intercambios entre usuarios.
 * No hay pagos: la compra es una solicitud que el vendedor acepta y que AMBOS confirman al entregar.
 * Reglas de integridad: cada cambio se hace dentro de una transacción que bloquea los productos
 * implicados (SELECT ... FOR UPDATE), y la BD tiene índices únicos como segundo candado.
 */
const OF_ESTADOS = ['pendiente' => 'Pendiente', 'aceptada' => 'Aceptada', 'rechazada' => 'Rechazada', 'cancelada' => 'Cancelada', 'completada' => 'Completada'];
const IN_ESTADOS = ['pendiente' => 'Pendiente', 'aceptado' => 'Aceptado', 'rechazado' => 'Rechazado', 'cancelado' => 'Cancelado', 'completado' => 'Completado'];
const OP_MAX_HORA = 20;   // solicitudes nuevas por usuario y hora (anti-spam)
const OP_MSG_MAX = 300;

class OperacionError extends RuntimeException {}

/** Ejecuta $fn en transacción. Devuelve [resultado, error|null]; los OperacionError son mensajes para el usuario. */
function op_tx(callable $fn): array {
    $pdo = db();
    $propia = !$pdo->inTransaction();
    if ($propia) $pdo->beginTransaction();
    try {
        $r = $fn();
        if ($propia) $pdo->commit();
        return [$r, null];
    } catch (OperacionError $e) {
        if ($propia && $pdo->inTransaction()) $pdo->rollBack();
        return [null, $e->getMessage()];
    } catch (PDOException $e) {
        if ($propia && $pdo->inTransaction()) $pdo->rollBack();
        if ($e->getCode() === '23000') return [null, 'Esa operación ya existe o el producto acaba de ser reservado. Actualiza la página.'];
        throw $e;
    } catch (Throwable $t) {
        if ($propia && $pdo->inTransaction()) $pdo->rollBack();
        throw $t;
    }
}

/** Bloquea las filas de producto (en orden de id, para evitar bloqueos cruzados) y las devuelve por id. */
function op_bloquear(array $ids): array {
    $ids = array_values(array_unique(array_map('intval', $ids)));
    sort($ids);
    $in = implode(',', array_fill(0, count($ids), '?'));
    $s = db()->prepare(
        "SELECT p.id, p.usuario_id, p.titulo, p.modalidad, p.precio, p.estado,
                (SELECT u.estado FROM usuarios u WHERE u.id = p.usuario_id) AS vendedor_estado
         FROM productos p WHERE p.id IN ($in) ORDER BY p.id FOR UPDATE"
    );
    $s->execute($ids);
    $out = [];
    foreach ($s->fetchAll() as $r) $out[(int)$r['id']] = $r;
    return $out;
}

function op_historial(string $tipo, int $id, ?int $uid, string $accion, ?string $antes, string $nuevo, ?string $detalle = null): void {
    db()->prepare('INSERT INTO historial_operaciones (tipo, operacion_id, usuario_id, accion, estado_anterior, estado_nuevo, detalle) VALUES (?,?,?,?,?,?,?)')
        ->execute([$tipo, $id, $uid, $accion, $antes, $nuevo, $detalle]);
}

/** Punto de enlace con las notificaciones (Etapa 6): si existe notificar(), se usa; si no, no hace nada. */
function op_avisar(int $uid, string $texto, string $url): void {
    if (function_exists('notificar')) notificar($uid, 'operacion', $texto, $url);
}

function producto_reservado(int $pid): bool {
    $s = db()->prepare("SELECT (SELECT COUNT(*) FROM ofertas WHERE producto_id = ? AND estado = 'aceptada')
                             + (SELECT COUNT(*) FROM intercambios WHERE estado = 'aceptado' AND (producto_id = ? OR producto_ofrecido_id = ?))");
    $s->execute([$pid, $pid, $pid]);
    return (int)$s->fetchColumn() > 0;
}

function tiene_operacion_completada(int $pid): bool {
    $s = db()->prepare("SELECT (SELECT COUNT(*) FROM ofertas WHERE producto_id = ? AND estado = 'completada')
                             + (SELECT COUNT(*) FROM intercambios WHERE estado = 'completado' AND (producto_id = ? OR producto_ofrecido_id = ?))");
    $s->execute([$pid, $pid, $pid]);
    return (int)$s->fetchColumn() > 0;
}

function op_parse_monto(string $t): ?string {
    $t = str_replace(',', '.', trim($t));
    if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $t) || (float)$t <= 0) return null;
    return number_format((float)$t, 2, '.', '');
}

function op_validar_usuario(int $uid): ?string {
    $u = usuario_por_id($uid);
    if (!$u || $u['estado'] !== 'activo') return 'Tu cuenta no está activa.';
    if (!$u['email_verificado_en']) return 'Verifica tu correo para realizar operaciones.';
    $s = db()->prepare("SELECT (SELECT COUNT(*) FROM ofertas WHERE comprador_id = ? AND creado_en > NOW() - INTERVAL 1 HOUR)
                             + (SELECT COUNT(*) FROM intercambios WHERE solicitante_id = ? AND creado_en > NOW() - INTERVAL 1 HOUR)");
    $s->execute([$uid, $uid]);
    if ((int)$s->fetchColumn() >= OP_MAX_HORA) return 'Hiciste muchas solicitudes seguidas. Espera un rato e inténtalo de nuevo.';
    return null;
}

function op_mensaje(string $m): ?string {
    $m = trim(preg_replace('/[^\P{C}\n]/u', '', $m) ?? '');
    return $m === '' ? null : $m;
}

/* ============ COMPRAS Y OFERTAS ============ */

/** $tipo: 'compra' (al precio publicado) u 'oferta' (precio propuesto). Devuelve [id|null, error|null]. */
function crear_solicitud(int $pid, int $uid, string $tipo, string $montoTxt, string $mensaje): array {
    if (!in_array($tipo, ['compra', 'oferta'], true)) return [null, 'Tipo de solicitud no válido.'];
    if (mb_strlen(trim($mensaje)) > OP_MSG_MAX) return [null, 'El mensaje puede tener máximo ' . OP_MSG_MAX . ' caracteres.'];
    if ($err = op_validar_usuario($uid)) return [null, $err];
    $mensaje = op_mensaje($mensaje);

    return op_tx(function () use ($pid, $uid, $tipo, $montoTxt, $mensaje) {
        $p = op_bloquear([$pid])[$pid] ?? null;
        if (!$p || $p['estado'] !== 'activo' || $p['vendedor_estado'] !== 'activo') throw new OperacionError('Este producto ya no está disponible.');
        if ((int)$p['usuario_id'] === $uid) throw new OperacionError('No puedes comprar ni ofertar por tu propio producto.');
        if ($p['modalidad'] === 'intercambio' || (float)$p['precio'] <= 0) throw new OperacionError('Este producto es solo para intercambio.');
        if (producto_reservado($pid)) throw new OperacionError('Este producto ya tiene una operación aceptada en curso.');

        if ($tipo === 'compra') {
            $monto = number_format((float)$p['precio'], 2, '.', '');
        } else {
            $monto = op_parse_monto($montoTxt);
            if ($monto === null) throw new OperacionError('Escribe un monto válido en soles, por ejemplo 150 o 149.90.');
            if ((float)$monto > (float)$p['precio']) throw new OperacionError('La oferta no puede superar el precio publicado.');
        }

        db()->prepare('INSERT INTO ofertas (producto_id, comprador_id, vendedor_id, tipo, monto, mensaje) VALUES (?,?,?,?,?,?)')
            ->execute([$pid, $uid, (int)$p['usuario_id'], $tipo, $monto, $mensaje]);
        $id = (int)db()->lastInsertId();
        op_historial('oferta', $id, $uid, 'creada', null, 'pendiente', $tipo === 'compra' ? 'Solicitud de compra' : 'Oferta de S/ ' . $monto);
        op_avisar((int)$p['usuario_id'], $tipo === 'compra' ? 'Nueva solicitud de compra' : 'Nueva oferta por tu producto', '/paginas/operaciones.php?ver=recibidas');
        return $id;
    });
}

function op_cerrar_oferta(array $o, string $nuevo, ?int $uid, string $accion, ?string $detalle = null): void {
    db()->prepare('UPDATE ofertas SET estado = ?, activa = NULL, aceptada_producto = NULL WHERE id = ?')->execute([$nuevo, $o['id']]);
    op_historial('oferta', (int)$o['id'], $uid, $accion, $o['estado'], $nuevo, $detalle);
}

function op_cerrar_intercambio(array $i, string $nuevo, ?int $uid, string $accion, ?string $detalle = null): void {
    db()->prepare('UPDATE intercambios SET estado = ?, activa = NULL, aceptado_producto = NULL, aceptado_ofrecido = NULL WHERE id = ?')->execute([$nuevo, $i['id']]);
    op_historial('intercambio', (int)$i['id'], $uid, $accion, $i['estado'], $nuevo, $detalle);
}

/** Rechaza (por el sistema) todo lo PENDIENTE que involucre a esos productos. Se usa dentro de una transacción. */
function op_rechazar_pendientes(array $pids, ?int $exceptoOferta, ?int $exceptoInter, string $motivo): void {
    $in = implode(',', array_fill(0, count($pids), '?'));
    $s = db()->prepare("SELECT * FROM ofertas WHERE estado = 'pendiente' AND producto_id IN ($in) FOR UPDATE");
    $s->execute($pids);
    foreach ($s->fetchAll() as $o) {
        if ($exceptoOferta !== null && (int)$o['id'] === $exceptoOferta) continue;
        op_cerrar_oferta($o, 'rechazada', null, 'rechazo_automatico', $motivo);
        op_avisar((int)$o['comprador_id'], 'Tu solicitud fue cerrada: ' . $motivo, '/paginas/operaciones.php?ver=enviadas');
    }
    $s = db()->prepare("SELECT * FROM intercambios WHERE estado = 'pendiente' AND (producto_id IN ($in) OR producto_ofrecido_id IN ($in)) FOR UPDATE");
    $s->execute(array_merge($pids, $pids));
    foreach ($s->fetchAll() as $i) {
        if ($exceptoInter !== null && (int)$i['id'] === $exceptoInter) continue;
        op_cerrar_intercambio($i, 'rechazado', null, 'rechazo_automatico', $motivo);
        op_avisar((int)$i['solicitante_id'], 'Tu propuesta de intercambio fue cerrada: ' . $motivo, '/paginas/operaciones.php?ver=enviadas');
    }
}

/** Para el módulo de productos: al vender/intercambiar/eliminar, lo pendiente se cierra. */
function op_rechazar_pendientes_de_producto(int $pid, string $motivo): void {
    op_rechazar_pendientes([$pid], null, null, $motivo);
}

/** $accion: aceptar | rechazar | cancelar | confirmar. Devuelve mensaje de error o null. */
function gestionar_oferta(int $id, int $uid, string $accion, ?string $motivo = null): ?string {
    $s = db()->prepare('SELECT producto_id FROM ofertas WHERE id = ? AND (comprador_id = ? OR vendedor_id = ?)');
    $s->execute([$id, $uid, $uid]);
    $pid = (int)$s->fetchColumn();
    if (!$pid) return 'No encontramos esa operación.';

    [, $err] = op_tx(function () use ($id, $uid, $accion, $pid, $motivo) {
        $p = op_bloquear([$pid])[$pid] ?? null;
        $s = db()->prepare('SELECT * FROM ofertas WHERE id = ? FOR UPDATE');
        $s->execute([$id]);
        $o = $s->fetch();
        if (!$p || !$o) throw new OperacionError('No encontramos esa operación.');
        $soyV = (int)$o['vendedor_id'] === $uid;
        $soyC = (int)$o['comprador_id'] === $uid;
        if (!$soyV && !$soyC) throw new OperacionError('No encontramos esa operación.');
        $est = $o['estado'];
        $otro = $soyV ? (int)$o['comprador_id'] : (int)$o['vendedor_id'];

        switch ($accion) {
            case 'aceptar':
                if (!$soyV) throw new OperacionError('Solo el vendedor puede aceptar.');
                if ($est !== 'pendiente') throw new OperacionError('Esta solicitud ya no está pendiente.');
                if ($p['estado'] !== 'activo' || $p['vendedor_estado'] !== 'activo') throw new OperacionError('El producto ya no está disponible.');
                if (producto_reservado($pid)) throw new OperacionError('Ya aceptaste otra operación para este producto.');
                db()->prepare("UPDATE ofertas SET estado = 'aceptada', aceptada_producto = ? WHERE id = ?")->execute([$pid, $id]);
                op_historial('oferta', $id, $uid, 'aceptada', $est, 'aceptada');
                op_avisar($otro, 'El vendedor aceptó tu solicitud', '/paginas/operaciones.php?ver=enviadas');
                op_rechazar_pendientes([$pid], $id, null, 'Se aceptó otra operación para este producto.');
                break;
            case 'rechazar':
                if (!$soyV) throw new OperacionError('Solo el vendedor puede rechazar.');
                if ($est !== 'pendiente') throw new OperacionError('Esta solicitud ya no está pendiente.');
                op_cerrar_oferta($o, 'rechazada', $uid, 'rechazada');
                op_avisar($otro, 'El vendedor rechazó tu solicitud', '/paginas/operaciones.php?ver=enviadas');
                break;
            case 'cancelar':
                if (!(($soyC && in_array($est, ['pendiente', 'aceptada'], true)) || ($soyV && $est === 'aceptada'))) throw new OperacionError('No puedes cancelar esta operación en su estado actual.');
                $motivoLimpio = $motivo ? trim(preg_replace('/[^\P{C}\n]/u', '', $motivo) ?? '') : null;
                $detalle = $motivoLimpio ? ('Cancelada por ' . ($soyC ? 'comprador' : 'vendedor') . ': ' . mb_substr($motivoLimpio, 0, 150)) : ('Cancelada por ' . ($soyC ? 'comprador' : 'vendedor'));
                op_cerrar_oferta($o, 'cancelada', $uid, 'cancelada', $detalle);
                op_avisar($otro, 'Se canceló una operación' . ($motivoLimpio ? ': ' . mb_substr($motivoLimpio, 0, 40) : ''), '/paginas/operaciones.php?ver=' . ($soyV ? 'enviadas' : 'recibidas'));
                break;
            case 'confirmar':
                if ($est !== 'aceptada') throw new OperacionError('Solo se confirma una operación aceptada.');
                if ($p['estado'] !== 'activo') throw new OperacionError('El producto ya no está disponible.');
                $col = $soyC ? 'confirma_comprador' : 'confirma_vendedor';
                db()->prepare("UPDATE ofertas SET $col = 1 WHERE id = ?")->execute([$id]);
                op_historial('oferta', $id, $uid, $soyC ? 'confirmada_comprador' : 'confirmada_vendedor', $est, $est);
                $ambos = ($soyC || (int)$o['confirma_comprador'] === 1) && ($soyV || (int)$o['confirma_vendedor'] === 1);
                if ($ambos) {
                    op_cerrar_oferta($o, 'completada', $uid, 'completada', 'Ambas partes confirmaron');
                    db()->prepare("UPDATE productos SET estado = 'vendido' WHERE id = ? AND estado = 'activo'")->execute([$pid]);
                    if (function_exists('auditar')) auditar($uid, 'oferta_completada', 'id=' . $id);
                    op_avisar($otro, 'Compra completada', '/paginas/operaciones.php?ver=historial');
                } else {
                    op_avisar($otro, 'La otra parte confirmó la operación; falta tu confirmación', '/paginas/operaciones.php?ver=' . ($soyV ? 'enviadas' : 'recibidas'));
                }
                break;
            default:
                throw new OperacionError('Acción no válida.');
        }
        return true;
    });
    return $err;
}

/* ============ INTERCAMBIOS ============ */

/** Productos del usuario que puede ofrecer: activos, que admiten intercambio y sin operación aceptada. */
function productos_para_intercambio(int $uid): array {
    $s = db()->prepare(
        "SELECT p.id, p.titulo, p.estado_producto, p.ubicacion, p.precio,
                (SELECT archivo FROM producto_imagenes i WHERE i.producto_id = p.id ORDER BY i.orden, i.id LIMIT 1) AS imagen
         FROM productos p
         WHERE p.usuario_id = ? AND p.estado = 'activo' AND p.modalidad <> 'venta'
           AND NOT EXISTS (SELECT 1 FROM intercambios x WHERE x.estado = 'aceptado' AND (x.producto_id = p.id OR x.producto_ofrecido_id = p.id))
           AND NOT EXISTS (SELECT 1 FROM ofertas o WHERE o.estado = 'aceptada' AND o.producto_id = p.id)
         ORDER BY p.actualizado_en DESC"
    );
    $s->execute([$uid]);
    return $s->fetchAll();
}

function proponer_intercambio(int $pid, int $uid, int $ofrecidoId, string $mensaje): array {
    if (mb_strlen(trim($mensaje)) > OP_MSG_MAX) return [null, 'El mensaje puede tener máximo ' . OP_MSG_MAX . ' caracteres.'];
    if ($pid === $ofrecidoId) return [null, 'Elige un producto distinto al que quieres.'];
    if ($err = op_validar_usuario($uid)) return [null, $err];
    $mensaje = op_mensaje($mensaje);

    return op_tx(function () use ($pid, $uid, $ofrecidoId, $mensaje) {
        $ps = op_bloquear([$pid, $ofrecidoId]);
        $p = $ps[$pid] ?? null;
        $q = $ps[$ofrecidoId] ?? null;
        if (!$p || $p['estado'] !== 'activo' || $p['vendedor_estado'] !== 'activo') throw new OperacionError('El producto que quieres ya no está disponible.');
        if ((int)$p['usuario_id'] === $uid) throw new OperacionError('No puedes proponer un intercambio por tu propio producto.');
        if ($p['modalidad'] === 'venta') throw new OperacionError('Ese producto es solo para venta.');
        if (!$q || (int)$q['usuario_id'] !== $uid) throw new OperacionError('Solo puedes ofrecer productos tuyos.');
        if ($q['estado'] !== 'activo') throw new OperacionError('El producto que ofreces ya no está disponible.');
        if ($q['modalidad'] === 'venta') throw new OperacionError('El producto que ofreces debe admitir intercambio. Edítalo y elige «Intercambio» o «Venta o intercambio».');
        if (producto_reservado($pid)) throw new OperacionError('Ese producto ya tiene una operación aceptada en curso.');
        if (producto_reservado($ofrecidoId)) throw new OperacionError('El producto que ofreces ya está comprometido en otra operación.');

        db()->prepare('INSERT INTO intercambios (producto_id, producto_ofrecido_id, solicitante_id, receptor_id, mensaje) VALUES (?,?,?,?,?)')
            ->execute([$pid, $ofrecidoId, $uid, (int)$p['usuario_id'], $mensaje]);
        $id = (int)db()->lastInsertId();
        op_historial('intercambio', $id, $uid, 'creado', null, 'pendiente', 'Ofrece «' . mb_substr($q['titulo'], 0, 60) . '»');
        op_avisar((int)$p['usuario_id'], 'Nueva propuesta de intercambio', '/paginas/operaciones.php?ver=recibidas');
        return $id;
    });
}

function gestionar_intercambio(int $id, int $uid, string $accion, ?string $motivo = null): ?string {
    $s = db()->prepare('SELECT producto_id, producto_ofrecido_id FROM intercambios WHERE id = ? AND (solicitante_id = ? OR receptor_id = ?)');
    $s->execute([$id, $uid, $uid]);
    $ids = $s->fetch();
    if (!$ids) return 'No encontramos esa operación.';

    [, $err] = op_tx(function () use ($id, $uid, $accion, $ids, $motivo) {
        $pid = (int)$ids['producto_id'];
        $qid = (int)$ids['producto_ofrecido_id'];
        $ps = op_bloquear([$pid, $qid]);
        $s = db()->prepare('SELECT * FROM intercambios WHERE id = ? FOR UPDATE');
        $s->execute([$id]);
        $i = $s->fetch();
        if (!$i || !isset($ps[$pid], $ps[$qid])) throw new OperacionError('No encontramos esa operación.');
        $soyR = (int)$i['receptor_id'] === $uid;
        $soyS = (int)$i['solicitante_id'] === $uid;
        if (!$soyR && !$soyS) throw new OperacionError('No encontramos esa operación.');
        $est = $i['estado'];
        $otro = $soyR ? (int)$i['solicitante_id'] : (int)$i['receptor_id'];
        $disponibles = $ps[$pid]['estado'] === 'activo' && $ps[$qid]['estado'] === 'activo';

        switch ($accion) {
            case 'aceptar':
                if (!$soyR) throw new OperacionError('Solo quien recibe la propuesta puede aceptarla.');
                if ($est !== 'pendiente') throw new OperacionError('Esta propuesta ya no está pendiente.');
                if (!$disponibles) throw new OperacionError('Uno de los productos ya no está disponible.');
                if (producto_reservado($pid) || producto_reservado($qid)) throw new OperacionError('Uno de los productos ya está comprometido en otra operación.');
                db()->prepare("UPDATE intercambios SET estado = 'aceptado', aceptado_producto = ?, aceptado_ofrecido = ? WHERE id = ?")->execute([$pid, $qid, $id]);
                op_historial('intercambio', $id, $uid, 'aceptado', $est, 'aceptado');
                op_avisar($otro, 'Aceptaron tu propuesta de intercambio', '/paginas/operaciones.php?ver=enviadas');
                op_rechazar_pendientes([$pid, $qid], null, $id, 'Se aceptó otra operación con uno de estos productos.');
                break;
            case 'rechazar':
                if (!$soyR) throw new OperacionError('Solo quien recibe la propuesta puede rechazarla.');
                if ($est !== 'pendiente') throw new OperacionError('Esta propuesta ya no está pendiente.');
                op_cerrar_intercambio($i, 'rechazado', $uid, 'rechazado');
                op_avisar($otro, 'Rechazaron tu propuesta de intercambio', '/paginas/operaciones.php?ver=enviadas');
                break;
            case 'cancelar':
                if (!(($soyS && in_array($est, ['pendiente', 'aceptado'], true)) || ($soyR && $est === 'aceptado'))) throw new OperacionError('No puedes cancelar esta operación en su estado actual.');
                $motivoLimpio = $motivo ? trim(preg_replace('/[^\P{C}\n]/u', '', $motivo) ?? '') : null;
                $detalle = $motivoLimpio ? ('Cancelado por ' . ($soyS ? 'solicitante' : 'receptor') . ': ' . mb_substr($motivoLimpio, 0, 150)) : ('Cancelado por ' . ($soyS ? 'solicitante' : 'receptor'));
                op_cerrar_intercambio($i, 'cancelado', $uid, 'cancelado', $detalle);
                op_avisar($otro, 'Se canceló un intercambio' . ($motivoLimpio ? ': ' . mb_substr($motivoLimpio, 0, 40) : ''), '/paginas/operaciones.php?ver=' . ($soyR ? 'enviadas' : 'recibidas'));
                break;
            case 'confirmar':
                if ($est !== 'aceptado') throw new OperacionError('Solo se confirma un intercambio aceptado.');
                if (!$disponibles) throw new OperacionError('Uno de los productos ya no está disponible.');
                $col = $soyS ? 'confirma_solicitante' : 'confirma_receptor';
                db()->prepare("UPDATE intercambios SET $col = 1 WHERE id = ?")->execute([$id]);
                op_historial('intercambio', $id, $uid, $soyS ? 'confirmado_solicitante' : 'confirmado_receptor', $est, $est);
                $ambos = ($soyS || (int)$i['confirma_solicitante'] === 1) && ($soyR || (int)$i['confirma_receptor'] === 1);
                if ($ambos) {
                    op_cerrar_intercambio($i, 'completado', $uid, 'completado', 'Ambas partes confirmaron');
                    db()->prepare("UPDATE productos SET estado = 'intercambiado' WHERE id IN (?, ?) AND estado = 'activo'")->execute([$pid, $qid]);
                    if (function_exists('auditar')) auditar($uid, 'intercambio_completado', 'id=' . $id);
                    op_avisar($otro, 'Intercambio completado', '/paginas/operaciones.php?ver=historial');
                } else {
                    op_avisar($otro, 'La otra parte confirmó el intercambio; falta tu confirmación', '/paginas/operaciones.php?ver=' . ($soyR ? 'enviadas' : 'recibidas'));
                }
                break;
            default:
                throw new OperacionError('Acción no válida.');
        }
        return true;
    });
    return $err;
}

/* ============ CONSULTAS ============ */

const OP_IMG = "(SELECT archivo FROM producto_imagenes i WHERE i.producto_id = %s.id ORDER BY i.orden, i.id LIMIT 1)";

function op_normalizar_oferta(array $r, int $uid): array {
    $soyV = (int)$r['vendedor_id'] === $uid;
    return [
        'tipo' => 'oferta', 'id' => (int)$r['id'], 'subtipo' => $r['tipo'], 'estado' => $r['estado'],
        'estado_texto' => OF_ESTADOS[$r['estado']] ?? $r['estado'],
        'soy' => $soyV ? 'vendedor' : 'comprador', 'otro' => $r['otro_nombre'], 'monto' => (float)$r['monto'],
        'mensaje' => $r['mensaje'], 'creado_en' => $r['creado_en'], 'actualizado_en' => $r['actualizado_en'],
        'yo_confirme' => (bool)($soyV ? $r['confirma_vendedor'] : $r['confirma_comprador']),
        'otro_confirmo' => (bool)($soyV ? $r['confirma_comprador'] : $r['confirma_vendedor']),
        'p' => ['id' => (int)$r['producto_id'], 'titulo' => $r['p_titulo'], 'imagen' => $r['p_imagen'], 'precio' => (float)$r['p_precio'],
                'estado' => $r['p_estado'], 'condicion' => $r['p_condicion'], 'ubicacion' => $r['p_ubicacion']],
    ];
}

function op_normalizar_intercambio(array $r, int $uid): array {
    $soyR = (int)$r['receptor_id'] === $uid;
    return [
        'tipo' => 'intercambio', 'id' => (int)$r['id'], 'estado' => $r['estado'],
        'estado_texto' => IN_ESTADOS[$r['estado']] ?? $r['estado'],
        'soy' => $soyR ? 'receptor' : 'solicitante', 'otro' => $r['otro_nombre'],
        'mensaje' => $r['mensaje'], 'creado_en' => $r['creado_en'], 'actualizado_en' => $r['actualizado_en'],
        'yo_confirme' => (bool)($soyR ? $r['confirma_receptor'] : $r['confirma_solicitante']),
        'otro_confirmo' => (bool)($soyR ? $r['confirma_solicitante'] : $r['confirma_receptor']),
        'p' => ['id' => (int)$r['producto_id'], 'titulo' => $r['p_titulo'], 'imagen' => $r['p_imagen'], 'precio' => (float)$r['p_precio'],
                'estado' => $r['p_estado'], 'condicion' => $r['p_condicion'], 'ubicacion' => $r['p_ubicacion']],
        'q' => ['id' => (int)$r['producto_ofrecido_id'], 'titulo' => $r['q_titulo'], 'imagen' => $r['q_imagen'], 'precio' => (float)$r['q_precio'],
                'estado' => $r['q_estado'], 'condicion' => $r['q_condicion'], 'ubicacion' => $r['q_ubicacion'], 'descripcion' => $r['q_descripcion']],
    ];
}

/** $rol: 'recibidas' | 'enviadas'. $grupo: 'abiertas' (pendiente/aceptada) | 'cerradas'. */
function ofertas_de(int $uid, string $rol, string $grupo): array {
    $mia = $rol === 'recibidas' ? 'o.vendedor_id' : 'o.comprador_id';
    $otro = $rol === 'recibidas' ? 'o.comprador_id' : 'o.vendedor_id';
    $est = $grupo === 'abiertas' ? "('pendiente','aceptada')" : "('rechazada','cancelada','completada')";
    $s = db()->prepare(
        "SELECT o.*, p.titulo AS p_titulo, p.precio AS p_precio, p.estado AS p_estado, p.estado_producto AS p_condicion, p.ubicacion AS p_ubicacion,
                " . sprintf(OP_IMG, 'p') . " AS p_imagen, u.nombre AS otro_nombre
         FROM ofertas o JOIN productos p ON p.id = o.producto_id JOIN usuarios u ON u.id = $otro
         WHERE $mia = ? AND o.estado IN $est ORDER BY o.actualizado_en DESC, o.id DESC LIMIT 100"
    );
    $s->execute([$uid]);
    return array_map(fn($r) => op_normalizar_oferta($r, $uid), $s->fetchAll());
}

function intercambios_de(int $uid, string $rol, string $grupo): array {
    $mia = $rol === 'recibidas' ? 'x.receptor_id' : 'x.solicitante_id';
    $otro = $rol === 'recibidas' ? 'x.solicitante_id' : 'x.receptor_id';
    $est = $grupo === 'abiertas' ? "('pendiente','aceptado')" : "('rechazado','cancelado','completado')";
    $s = db()->prepare(
        "SELECT x.*, u.nombre AS otro_nombre,
                p.titulo AS p_titulo, p.precio AS p_precio, p.estado AS p_estado, p.estado_producto AS p_condicion, p.ubicacion AS p_ubicacion, " . sprintf(OP_IMG, 'p') . " AS p_imagen,
                q.titulo AS q_titulo, q.precio AS q_precio, q.estado AS q_estado, q.estado_producto AS q_condicion, q.ubicacion AS q_ubicacion, q.descripcion AS q_descripcion, " . sprintf(OP_IMG, 'q') . " AS q_imagen
         FROM intercambios x JOIN productos p ON p.id = x.producto_id JOIN productos q ON q.id = x.producto_ofrecido_id JOIN usuarios u ON u.id = $otro
         WHERE $mia = ? AND x.estado IN $est ORDER BY x.actualizado_en DESC, x.id DESC LIMIT 100"
    );
    $s->execute([$uid]);
    return array_map(fn($r) => op_normalizar_intercambio($r, $uid), $s->fetchAll());
}

/** Operaciones terminadas del usuario. $filtro: todas | compras | ventas | ofertas | intercambios. */
function historial_de(int $uid, string $filtro = 'todas'): array {
    $lista = [];
    foreach (['recibidas', 'enviadas'] as $rol) {
        foreach (ofertas_de($uid, $rol, 'cerradas') as $o) $lista[] = $o;
        foreach (intercambios_de($uid, $rol, 'cerradas') as $i) $lista[] = $i;
    }
    $lista = array_values(array_filter($lista, function ($x) use ($filtro) {
        return match ($filtro) {
            'compras' => $x['tipo'] === 'oferta' && $x['soy'] === 'comprador' && $x['estado'] === 'completada',
            'ventas' => $x['tipo'] === 'oferta' && $x['soy'] === 'vendedor' && $x['estado'] === 'completada',
            'ofertas' => $x['tipo'] === 'oferta',
            'intercambios' => $x['tipo'] === 'intercambio',
            default => true,
        };
    }));
    usort($lista, fn($a, $b) => strcmp($b['actualizado_en'], $a['actualizado_en']) ?: ($b['id'] <=> $a['id']));
    return $lista;
}

/** Cambios registrados de varias operaciones del mismo tipo: [id => [filas]]. */
function cambios_de(string $tipo, array $ids): array {
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $s = db()->prepare("SELECT h.*, u.nombre AS quien FROM historial_operaciones h LEFT JOIN usuarios u ON u.id = h.usuario_id
                        WHERE h.tipo = ? AND h.operacion_id IN ($in) ORDER BY h.id");
    $s->execute(array_merge([$tipo], $ids));
    $out = [];
    foreach ($s->fetchAll() as $r) $out[(int)$r['operacion_id']][] = $r;
    return $out;
}

function contar_pendientes_recibidas(int $uid): int {
    $s = db()->prepare("SELECT (SELECT COUNT(*) FROM ofertas WHERE vendedor_id = ? AND estado = 'pendiente')
                             + (SELECT COUNT(*) FROM intercambios WHERE receptor_id = ? AND estado = 'pendiente')");
    $s->execute([$uid, $uid]);
    return (int)$s->fetchColumn();
}

/** Solicitudes abiertas del usuario sobre un producto (para el detalle): ['oferta'=>fila|null, 'intercambio'=>fila|null]. */
function solicitudes_abiertas_en(int $pid, int $uid): array {
    $s = db()->prepare("SELECT id, tipo, estado FROM ofertas WHERE producto_id = ? AND comprador_id = ? AND estado IN ('pendiente','aceptada') LIMIT 1");
    $s->execute([$pid, $uid]);
    $o = $s->fetch() ?: null;
    $s = db()->prepare("SELECT id, estado FROM intercambios WHERE producto_id = ? AND solicitante_id = ? AND estado IN ('pendiente','aceptado') LIMIT 1");
    $s->execute([$pid, $uid]);
    return ['oferta' => $o, 'intercambio' => $s->fetch() ?: null];
}

/** Botones que corresponden a esta operación para el usuario actual: [[accion, texto, clase, confirmación]]. */
function acciones_disponibles(array $op): array {
    $a = [];
    $e = $op['estado'];
    $esOferta = $op['tipo'] === 'oferta';
    $recibe = in_array($op['soy'], ['vendedor', 'receptor'], true);
    $pide = !$recibe;
    $pend = $e === 'pendiente';
    $acept = $e === ($esOferta ? 'aceptada' : 'aceptado');

    if ($recibe && $pend) {
        $a[] = ['aceptar', 'Aceptar', 'btn--primario', ''];
        $a[] = ['rechazar', 'Rechazar', 'btn--linea', '¿Rechazar esta ' . ($esOferta ? 'solicitud' : 'propuesta') . '?'];
    }
    if ($acept && !$op['yo_confirme']) {
        $a[] = ['confirmar', $esOferta ? 'Confirmar que se concretó' : 'Confirmar intercambio', 'btn--primario',
                '¿Confirmas que la operación se realizó? Cuando ambos confirmen, se cerrará.'];
    }
    if (($pide && ($pend || $acept)) || ($recibe && $acept)) {
        $a[] = ['cancelar', 'Cancelar', 'btn--peligro', '¿Cancelar esta operación?'];
    }
    return $a;
}
