<?php
const FAV_MAX = 200;

function agregar_favorito(int $uid, int $pid): ?string {
    $p = producto_por_id($pid);
    if (!$p) return 'Ese producto no existe.';
    if ((int)$p['usuario_id'] === $uid) return 'No puedes guardar tu propio producto.';
    $s = db()->prepare('SELECT COUNT(*) FROM favoritos WHERE usuario_id = ?');
    $s->execute([$uid]);
    if ((int)$s->fetchColumn() >= FAV_MAX && !es_favorito($uid, $pid)) return 'Llegaste al máximo de ' . FAV_MAX . ' favoritos. Quita alguno para guardar más.';
    db()->prepare('INSERT IGNORE INTO favoritos (usuario_id, producto_id) VALUES (?, ?)')->execute([$uid, $pid]);
    return null;
}

function quitar_favorito(int $uid, int $pid): void {
    db()->prepare('DELETE FROM favoritos WHERE usuario_id = ? AND producto_id = ?')->execute([$uid, $pid]);
}

function es_favorito(int $uid, int $pid): bool {
    $s = db()->prepare('SELECT 1 FROM favoritos WHERE usuario_id = ? AND producto_id = ?');
    $s->execute([$uid, $pid]);
    return (bool)$s->fetchColumn();
}

function favoritos_de(int $uid): array {
    $s = db()->prepare(
        SQL_PRODUCTO . ', f.creado_en AS favorito_en' . SQL_DESDE .
        " JOIN favoritos f ON f.producto_id = p.id
          WHERE f.usuario_id = ? AND p.estado <> 'eliminado'
          ORDER BY f.creado_en DESC LIMIT " . FAV_MAX
    );
    $s->execute([$uid]);
    return $s->fetchAll();
}
