<?php

function usuario_por_id(int $id): ?array {
    $s = db()->prepare('SELECT * FROM usuarios WHERE id=?');
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

function usuario_por_email(string $e): ?array {
    $s = db()->prepare('SELECT * FROM usuarios WHERE email=?');
    $s->execute([$e]);
    return $s->fetch() ?: null;
}

function crear_usuario(string $n, string $e, string $hash): int {
    db()->prepare('INSERT INTO usuarios (nombre,email,clave_hash) VALUES (?,?,?)')->execute([$n, $e, $hash]);
    return (int) db()->lastInsertId();
}

function cambiar_clave_usuario(int $id, string $hash): int {
    db()->prepare('UPDATE usuarios SET clave_hash=?, version_sesion=version_sesion+1 WHERE id=?')->execute([$hash, $id]);
    return (int) usuario_por_id($id)['version_sesion'];
}

function subir_version_sesion(int $id): int {
    db()->prepare('UPDATE usuarios SET version_sesion=version_sesion+1 WHERE id=?')->execute([$id]);
    return (int) usuario_por_id($id)['version_sesion'];
}
