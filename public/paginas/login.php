<?php require __DIR__.'/../../app/ayudas/bootstrap.php';
if (usuario_actual()) redirigir(BASE.'/index.php');
$errores = []; $valores = []; $volver = destino_seguro($_GET['volver'] ?? $_POST['volver'] ?? null);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verificar_csrf(); $valores['email'] = trim((string)($_POST['email'] ?? ''));
  $m = iniciar_login((string)($_POST['email'] ?? ''), (string)($_POST['clave'] ?? ''));
  if ($m === null) redirigir($volver);
  $errores['general'] = $m;
}
$titulo = 'Iniciar sesión'; 
require APP.'/vistas/cabecera_simple.php'; // Usa la cabecera limpia
$o = ['errores'=>$errores,'valores'=>$valores]; ?>

<div class="contenedor">
  <div class="auth my-4">
    <h1 style="font-size:1.5rem;text-align:center" class="mb-3">Iniciar sesión</h1>
    <form method="post" data-validar="login" novalidate>
      <?= csrf_campo() ?>
      <input type="hidden" name="volver" value="<?= e($volver) ?>">
      <?php aviso_error($errores);
      campo('email','Correo','email',$o+['auto'=>'email']); 
      campo('clave','Contraseña','password',$o+['auto'=>'current-password']); ?>
      <button class="btn btn--primario btn--bloque mt-3" type="submit">Entrar</button>
    </form>
    <p style="text-align:center;margin:16px 0 4px"><a href="recuperar.php">¿Olvidaste tu contraseña?</a></p>
    <p style="text-align:center;margin:0">¿Primera vez? <a href="registro.php">Crea tu cuenta</a></p>
  </div>
</div>

<?php require APP.'/vistas/pie.php'; ?>