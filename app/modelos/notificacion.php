<?php
/*
 * ReUns - Etapa 6: notificaciones. notificar() es el punto único que usa el resto del sistema
 * (operaciones, chat). Nunca debe romper la acción principal: si falla, se registra y se sigue.
 */
const NOTIF_TIPOS = ['operacion', 'mensaje', 'sistema'];

function notificar(int $uid, string $tipo, string $texto, ?string $url = null): void {
    $texto = mb_substr(trim($texto), 0, 160);
    if ($texto === '' || $uid <= 0) return;
    if (!in_array($tipo, NOTIF_TIPOS, true)) $tipo = 'sistema';
    // Solo rutas internas de /paginas/: nunca se guarda un enlace externo.
    if ($url !== null && !preg_match('#^/paginas/[a-z_]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$#', $url)) $url = null;

    try {
        // Varios mensajes seguidos de la misma conversación = UNA notificación sin leer.
        if ($tipo === 'mensaje' && $url !== null) {
            // (No se usa rowCount(): MySQL cuenta 0 filas cuando el UPDATE deja los mismos valores.)
            $s = db()->prepare("SELECT id FROM notificaciones WHERE usuario_id = ? AND tipo = 'mensaje' AND url = ? AND leida_en IS NULL ORDER BY id DESC LIMIT 1");
            $s->execute([$uid, $url]);
            if ($existente = $s->fetchColumn()) {
                db()->prepare('UPDATE notificaciones SET texto = ?, creado_en = NOW() WHERE id = ?')->execute([$texto, $existente]);
                return;
            }
        }
        db()->prepare('INSERT INTO notificaciones (usuario_id, tipo, texto, url) VALUES (?,?,?,?)')->execute([$uid, $tipo, $texto, $url]);
    } catch (Throwable $e) {
        error_log('[ReUns] notificar: ' . $e->getMessage());
    }
}

function notificaciones_de(int $uid, int $limite = 40): array {
    $limite = max(1, min(100, $limite));
    $s = db()->prepare("SELECT id, tipo, texto, url, leida_en, creado_en FROM notificaciones WHERE usuario_id = ? ORDER BY id DESC LIMIT $limite");
    $s->execute([$uid]);
    return $s->fetchAll();
}

function contar_notificaciones_no_leidas(int $uid): int {
    $s = db()->prepare('SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida_en IS NULL');
    $s->execute([$uid]);
    return (int)$s->fetchColumn();
}

/** Solo marca si la notificación es DEL usuario. Devuelve true si cambió algo. */
function marcar_notificacion_leida(int $id, int $uid): bool {
    $s = db()->prepare('UPDATE notificaciones SET leida_en = NOW() WHERE id = ? AND usuario_id = ? AND leida_en IS NULL');
    $s->execute([$id, $uid]);
    return $s->rowCount() > 0;
}

function marcar_todas_leidas(int $uid): int {
    $s = db()->prepare('UPDATE notificaciones SET leida_en = NOW() WHERE usuario_id = ? AND leida_en IS NULL');
    $s->execute([$uid]);
    return $s->rowCount();
}

/** Limpieza opcional (ejecutar de vez en cuando): borra las leídas con más de $dias días. */
function purgar_notificaciones(int $dias = 90): int {
    $s = db()->prepare('DELETE FROM notificaciones WHERE leida_en IS NOT NULL AND creado_en < NOW() - INTERVAL ? DAY');
    $s->execute([max(7, $dias)]);
    return $s->rowCount();
}
