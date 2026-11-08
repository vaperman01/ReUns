<?php require __DIR__.'/../../app/ayudas/bootstrap.php';
if (usuario_actual()) redirigir(BASE.'/index.php');
$errores = []; $valores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verificar_csrf(); $valores = ['nombre'=>trim((string)($_POST['nombre'] ?? '')), 'email'=>trim((string)($_POST['email'] ?? ''))];
  [$errores, $id] = registrar_usuario($_POST);
  if ($id) { abrir_sesion(usuario_por_id($id)); flash('ok', 'Cuenta creada. Te enviamos un correo para verificar tu dirección.'); redirigir(BASE.'/index.php'); }
}
$titulo = 'Crear cuenta'; 
require APP.'/vistas/cabecera_simple.php'; // Usa la cabecera limpia
$o = ['errores'=>$errores,'valores'=>$valores]; ?>

<div class="contenedor">
  <div class="auth my-4">
    <h1 style="font-size:1.5rem;text-align:center" class="mb-3">Crear cuenta</h1>
    <form method="post" data-validar="nueva" novalidate>
      <?= csrf_campo(); aviso_error($errores);
      campo('nombre','Nombre','text',$o+['auto'=>'name']); 
      campo('email','Correo','email',$o+['auto'=>'email']);
      campo('clave','Contraseña (8+ caracteres, con letra y número)','password',$o+['auto'=>'new-password']);
      campo('clave2','Repite la contraseña','password',$o+['auto'=>'new-password']); ?>
      
      <div class="campo campo--check mt-2">
        <input type="checkbox" id="acepto" name="acepto" value="1" required>
        <label for="acepto" style="display:inline;font-weight:400">
          Acepto los <a href="<?= BASE ?>/paginas/terminos.php" target="_blank">términos de uso</a> y la <a href="<?= BASE ?>/paginas/privacidad.php" target="_blank">política de privacidad</a>.
        </label>
        <p class="error"><?= e($errores['acepto'] ?? '') ?></p>
      </div>

      <button class="btn btn--primario btn--bloque mt-3" type="submit">Crear cuenta</button>
    </form>
    <p style="text-align:center;margin:16px 0 0">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
  </div>
</div>

<?php require APP.'/vistas/pie.php'; ?>
