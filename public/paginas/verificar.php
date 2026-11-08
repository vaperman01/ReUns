<?php require __DIR__.'/../../app/ayudas/bootstrap.php';
$tok = (string)($_GET['token'] ?? '');
if (preg_match('/^[a-f0-9]{64}$/', $tok) && verificar_correo($tok)) flash('ok', 'Correo verificado. ¡Gracias!');
else flash('error', 'El enlace de verificación no es válido o ya venció. Pide otro desde tu perfil.');
redirigir(usuario_actual() ? BASE.'/paginas/perfil.php' : BASE.'/paginas/login.php');
