<?php
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$u = requerir_login();
$pid = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

// Iniciar un chat crea datos: solo por POST con token CSRF. Un GET devuelve al producto.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir(BASE . '/paginas/producto.php?id=' . $pid);
}

verificar_csrf();
[$cid, $err] = iniciar_conversacion($pid, (int)$u['id']);

if ($cid) {
    redirigir(BASE . '/paginas/conversacion.php?c=' . $cid);
}

flash('error', $err ?? 'No se pudo iniciar la conversación.');
redirigir(BASE . '/paginas/producto.php?id=' . $pid);
