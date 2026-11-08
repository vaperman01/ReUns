<?php require __DIR__.'/../../app/ayudas/bootstrap.php';
$u = requerir_login();
if (!$u['email_verificado_en']) pagina_error(403, 'Verifica tu correo para publicar', 'Revisa tu bandeja (o la carpeta storage/correos en pruebas) o reenvía el correo desde tu perfil.');
$errores = []; $valores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verificar_csrf(); $valores = $_POST;
  [$errores, $id] = publicar_producto($u, $_POST, $_FILES['fotos'] ?? null);
  if ($id) { flash('ok', 'Producto publicado.'); redirigir(BASE.'/paginas/producto.php?id='.$id); }
}
$titulo = 'Publicar producto'; 
require APP.'/vistas/cabecera_simple.php'; // Usa la cabecera limpia
$o = ['errores' => $errores, 'valores' => $valores]; $producto = null; ?>

<div class="contenedor py-3">
  <div class="mb-3">
    <a href="<?= BASE ?>/index.php" style="text-decoration:none; color:var(--gris); font-size:0.9rem;">← Cancelar y volver</a>
  </div>
  <div class="form-producto">
    <h1 style="font-size:1.5rem">Publicar un producto</h1>
    <?php require APP.'/vistas/form_producto.php'; ?>
  </div>
</div>

<?php require APP.'/vistas/pie.php'; ?>