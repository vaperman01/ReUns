<?php
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$u = requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir(BASE . '/paginas/favoritos.php');
}

verificar_csrf();

$pid = (int)($_POST['producto_id'] ?? 0);
$accion = (string)($_POST['accion'] ?? '');
$volver = destino_seguro($_POST['volver'] ?? null);   // solo rutas locales: sin redirecciones abiertas

if ($accion === 'agregar') {
    $err = agregar_favorito((int)$u['id'], $pid);
    flash($err ? 'error' : 'ok', $err ?? 'Guardado en tus favoritos.');
} elseif ($accion === 'quitar') {
    quitar_favorito((int)$u['id'], $pid);
    flash('ok', 'Quitado de tus favoritos.');
} else {
    flash('error', 'Acción no válida.');
}

redirigir($volver);
