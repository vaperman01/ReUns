<?php require __DIR__.'/../../app/ayudas/bootstrap.php';
$u = requerir_login(); $id = (int)($_GET['id'] ?? 0);
$producto = producto_propio($id, (int)$u['id']); // solo el dueño; para cualquier otro es "no existe"
if (!$producto) pagina_error(404, 'Publicación no encontrada', 'No existe o no es tuya.');
$errores = []; $valores = $producto;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verificar_csrf(); $valores = $_POST;
  $errores = editar_producto($u, $id, $_POST, $_FILES['fotos'] ?? null, (array)($_POST['quitar'] ?? []));
  if (!$errores) { flash('ok', 'Cambios guardados.'); redirigir(BASE.'/paginas/producto.php?id='.$id); }
}
$titulo = 'Editar producto'; require APP.'/vistas/cabecera.php'; $o = ['errores' => $errores, 'valores' => $valores]; ?>
<div class="contenedor"><div class="form-producto"><h1 style="font-size:1.5rem">Editar publicación</h1><?php require APP.'/vistas/form_producto.php'; ?></div></div>
<?php require APP.'/vistas/pie.php'; ?>
