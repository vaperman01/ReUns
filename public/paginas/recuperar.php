<?php require __DIR__.'/../../app/ayudas/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verificar_csrf(); solicitar_recuperacion((string)($_POST['email'] ?? ''));
  flash('ok', 'Si ese correo está registrado, te enviamos un enlace para crear una contraseña nueva.'); redirigir(BASE.'/paginas/login.php');
}
$titulo = 'Recuperar contraseña'; require APP.'/vistas/cabecera.php'; ?>
<div class="contenedor"><div class="auth"><h1 style="font-size:1.4rem">Recuperar contraseña</h1>
  <p>Escribe tu correo y te enviaremos un enlace válido por 1 hora.</p>
  <form method="post" data-validar="login" novalidate><?= csrf_campo(); campo('email','Correo','email',['auto'=>'email']); ?>
    <button class="btn btn--primario btn--bloque" type="submit">Enviar enlace</button></form>
</div></div>
<?php require APP.'/vistas/pie.php'; ?>
