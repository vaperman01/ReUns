<?php
/*
 * ReUns - Etapa 6: chat entre comprador y vendedor, siempre ligado a un producto.
 * Solo los dos participantes de la conversación pueden leer o escribir: toda función
 * recibe el id del usuario y comprueba la pertenencia en la propia consulta SQL.
 */
const CHAT_MAX = 1000;       // caracteres por mensaje
const CHAT_MAX_MIN = 20;     // mensajes por usuario y minuto (anti-spam)

function chat_limpiar(string $t): string {
    $t = str_replace(["\r\n", "\r"], "\n", $t);
    return trim(preg_replace('/[^\P{Cc}\n]/u', '', $t) ?? '');   // quita caracteres de control, conserva saltos de línea
}

function chat_validar_usuario(int $uid): ?string {
    $u = usuario_por_id($uid);
    if (!$u || $u['estado'] !== 'activo') return 'Tu cuenta no está activa.';
    if (!$u['email_verificado_en']) return 'Verifica tu correo para usar el chat.';
    return null;
}

/** Devuelve [id_conversacion|null, error|null]. Si ya existe una conversación con ese producto, la reutiliza. */
function iniciar_conversacion(int $pid, int $uid): array {
    if ($err = chat_validar_usuario($uid)) return [null, $err];
    $p = producto_por_id($pid);
    if (!$p || ($p['vendedor_estado'] ?? '') !== 'activo' || $p['estado'] === 'oculto') return [null, 'Este producto ya no está disponible.'];
    if ((int)$p['usuario_id'] === $uid) return [null, 'No puedes chatear contigo mismo.'];

    $buscar = db()->prepare('SELECT id FROM conversaciones WHERE producto_id = ? AND comprador_id = ?');
    $buscar->execute([$pid, $uid]);
    if ($id = $buscar->fetchColumn()) return [(int)$id, null];

    if ($p['estado'] !== 'activo') return [null, 'Este producto ya no está disponible.'];
    try {
        db()->prepare('INSERT INTO conversaciones (producto_id, comprador_id, vendedor_id) VALUES (?,?,?)')->execute([$pid, $uid, (int)$p['usuario_id']]);
        return [(int)db()->lastInsertId(), null];
    } catch (PDOException $e) {
        if ($e->getCode() !== '23000') throw $e;       // carrera: otra petición la creó justo antes
        $buscar->execute([$pid, $uid]);
        return [(int)$buscar->fetchColumn(), null];
    }
}

/** La conversación SOLO si $uid es uno de los dos participantes; si no, null (para el resto "no existe"). */
function conversacion_de(int $cid, int $uid): ?array {
    $s = db()->prepare(
        "SELECT c.*, p.titulo AS p_titulo, p.estado AS p_estado, p.precio AS p_precio,
                (SELECT archivo FROM producto_imagenes i WHERE i.producto_id = p.id ORDER BY i.orden, i.id LIMIT 1) AS p_imagen,
                u.id AS otro_id, u.nombre AS otro_nombre, u.estado AS otro_estado,
                IF(c.comprador_id = ?, 'comprador', 'vendedor') AS soy
         FROM conversaciones c
         JOIN productos p ON p.id = c.producto_id
         JOIN usuarios u ON u.id = IF(c.comprador_id = ?, c.vendedor_id, c.comprador_id)
         WHERE c.id = ? AND (c.comprador_id = ? OR c.vendedor_id = ?)"
    );
    $s->execute([$uid, $uid, $cid, $uid, $uid]);
    return $s->fetch() ?: null;
}

/** Devuelve [id_mensaje|null, error|null]. */
function enviar_mensaje(int $cid, int $uid, string $texto): array {
    $c = conversacion_de($cid, $uid);
    if (!$c) return [null, 'No encontramos esa conversación.'];
    if ($err = chat_validar_usuario($uid)) return [null, $err];
    if ($c['p_estado'] === 'eliminado') return [null, 'Esta publicación fue eliminada; ya no se puede escribir.'];
    if ($c['otro_estado'] !== 'activo') return [null, 'La otra persona ya no tiene la cuenta activa.'];

    $texto = chat_limpiar($texto);
    if ($texto === '') return [null, 'Escribe un mensaje.'];
    if (mb_strlen($texto) > CHAT_MAX) return [null, 'El mensaje puede tener máximo ' . CHAT_MAX . ' caracteres.'];

    $r = db()->prepare('SELECT COUNT(*) FROM mensajes WHERE remitente_id = ? AND creado_en > NOW() - INTERVAL 1 MINUTE');
    $r->execute([$uid]);
    if ((int)$r->fetchColumn() >= CHAT_MAX_MIN) return [null, 'Estás enviando mensajes muy rápido. Espera un momento.'];

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO mensajes (conversacion_id, remitente_id, texto) VALUES (?,?,?)')->execute([$cid, $uid, $texto]);
        $id = (int)$pdo->lastInsertId();
        $col = $c['soy'] === 'comprador' ? 'leido_comprador' : 'leido_vendedor';
        $pdo->prepare("UPDATE conversaciones SET ultimo_mensaje_en = NOW(), $col = ? WHERE id = ?")->execute([$id, $cid]);
        $pdo->commit();
    } catch (Throwable $t) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $t;
    }

    $yo = usuario_por_id($uid);
    notificar((int)$c['otro_id'], 'mensaje', 'Nuevo mensaje de ' . $yo['nombre'] . ' sobre «' . mb_substr($c['p_titulo'], 0, 50) . '»', '/paginas/conversacion.php?c=' . $cid);
    return [$id, null];
}

/**
 * Mensajes en orden cronológico. $desde = 0 → los últimos 100; $desde > 0 → solo los posteriores a ese id.
 * Devuelve null si $uid no participa. Marca la conversación como leída para $uid.
 */
function mensajes_de(int $cid, int $uid, int $desde = 0): ?array {
    $c = conversacion_de($cid, $uid);
    if (!$c) return null;

    if ($desde > 0) {
        $s = db()->prepare('SELECT m.id, m.remitente_id, m.texto, m.creado_en FROM mensajes m WHERE m.conversacion_id = ? AND m.id > ? ORDER BY m.id ASC LIMIT 100');
        $s->execute([$cid, $desde]);
    } else {
        $s = db()->prepare('SELECT * FROM (SELECT m.id, m.remitente_id, m.texto, m.creado_en FROM mensajes m WHERE m.conversacion_id = ? ORDER BY m.id DESC LIMIT 100) t ORDER BY id ASC');
        $s->execute([$cid]);
    }
    $filas = $s->fetchAll();

    if ($filas) {
        $ultimo = (int)end($filas)['id'];
        $col = $c['soy'] === 'comprador' ? 'leido_comprador' : 'leido_vendedor';
        db()->prepare("UPDATE conversaciones SET $col = GREATEST($col, ?) WHERE id = ?")->execute([$ultimo, $cid]);
    }
    // Al abrir el chat, la notificación de mensajes de esa conversación deja de estar pendiente.
    db()->prepare("UPDATE notificaciones SET leida_en = NOW() WHERE usuario_id = ? AND tipo = 'mensaje' AND url = ? AND leida_en IS NULL")
        ->execute([$uid, '/paginas/conversacion.php?c=' . $cid]);

    return array_map(fn($m) => [
        'id' => (int)$m['id'],
        'mio' => (int)$m['remitente_id'] === $uid,
        'texto' => $m['texto'],
        'creado_en' => $m['creado_en'],
        'hora' => date('d/m H:i', strtotime($m['creado_en'])),
    ], $filas);
}

function conversaciones_de(int $uid): array {
    $s = db()->prepare(
        "SELECT c.id, c.ultimo_mensaje_en, c.creado_en, p.id AS p_id, p.titulo AS p_titulo, p.estado AS p_estado,
                (SELECT archivo FROM producto_imagenes i WHERE i.producto_id = p.id ORDER BY i.orden, i.id LIMIT 1) AS p_imagen,
                u.nombre AS otro_nombre,
                (SELECT m.texto FROM mensajes m WHERE m.conversacion_id = c.id ORDER BY m.id DESC LIMIT 1) AS ultimo_texto,
                (SELECT COUNT(*) FROM mensajes m WHERE m.conversacion_id = c.id AND m.remitente_id <> ?
                    AND m.id > IF(c.comprador_id = ?, c.leido_comprador, c.leido_vendedor)) AS no_leidos
         FROM conversaciones c
         JOIN productos p ON p.id = c.producto_id
         JOIN usuarios u ON u.id = IF(c.comprador_id = ?, c.vendedor_id, c.comprador_id)
         WHERE c.comprador_id = ? OR c.vendedor_id = ?
         ORDER BY COALESCE(c.ultimo_mensaje_en, c.creado_en) DESC, c.id DESC LIMIT 100"
    );
    $s->execute([$uid, $uid, $uid, $uid, $uid]);
    return $s->fetchAll();
}

function contar_mensajes_no_leidos(int $uid): int {
    $s = db()->prepare(
        "SELECT COUNT(*) FROM mensajes m JOIN conversaciones c ON c.id = m.conversacion_id
         WHERE (c.comprador_id = ? OR c.vendedor_id = ?) AND m.remitente_id <> ?
           AND m.id > IF(c.comprador_id = ?, c.leido_comprador, c.leido_vendedor)"
    );
    $s->execute([$uid, $uid, $uid, $uid]);
    return (int)$s->fetchColumn();
}
