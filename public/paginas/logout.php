<?php require __DIR__.'/../../app/ayudas/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirigir(BASE.'/index.php');
verificar_csrf(); cerrar_sesion(); iniciar_sesion(); flash('ok', 'Cerraste sesión.'); redirigir(BASE.'/index.php');
