<?php require __DIR__.'/../../app/ayudas/bootstrap.php';
$tok = (string)($_GET['token'] ?? $_POST['token'] ?? ''); $errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verificar_csrf(); $errores = restablecer_clave($tok, (string)($_POST['clave'] ?? ''), (string)($_POST['clave2'] ?? ''));
  if (!$errores) { flash('ok', 'Contraseña actualizada. Inicia sesión con la nueva.'); redirigir(BASE.'/paginas/login.php'); }
}
if (!preg_match('/^[a-f0-9]{64}$/', $tok) || ($_SERVER['REQUEST_METHOD'] !== 'POST' && !token_valido($tok, 'recuperar')))
  pagina_error(400, 'Enlace no válido', 'El enlace no es válido o ya venció. Pide uno nuevo desde "¿Olvidaste tu contraseña?".');
$titulo = 'Nueva contraseña'; require APP.'/vistas/cabecera.php'; $o = ['errores'=>$errores]; ?>
<div class="contenedor"><div class="auth"><h1 style="font-size:1.4rem">Crea tu nueva contraseña</h1>
  <form method="post" data-validar="nueva" novalidate><?= csrf_campo() ?><input type="hidden" name="token" value="<?= e($tok) ?>"><?php aviso_error($errores);
    campo('clave','Contraseña nueva','password',$o+['auto'=>'new-password']); campo('clave2','Repite la contraseña','password',$o+['auto'=>'new-password']); ?>
    <button class="btn btn--primario btn--bloque" type="submit">Guardar contraseña</button></form>
</div></div>
<?php require APP.'/vistas/pie.php'; ?>
