<?php
// OPCIONAL: carga 10 productos de ejemplo (sin fotos) para ver el catálogo lleno.  Uso: php base_datos/cargar_demo.php
// Crea el usuario "Vendedor Demo" con una contraseña aleatoria que nadie conoce (no se puede iniciar sesión con él).
// Para borrar todo lo demo:  DELETE FROM usuarios WHERE email='demo@reuns.local';  (borra antes sus productos)
if (PHP_SAPI !== 'cli') exit('Solo desde la terminal.');
require __DIR__.'/../app/config/config.php'; require APP.'/config/db.php'; require APP.'/modelos/usuario.php'; require APP.'/modelos/producto.php';
$u = usuario_por_email('demo@reuns.local');
if (!$u) { $id = crear_usuario('Vendedor Demo', 'demo@reuns.local', password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)); db()->exec("UPDATE usuarios SET email_verificado_en=NOW(), ubicacion='Chimbote' WHERE id=$id"); }
else $id = (int)$u['id'];
$datos = [['Laptop Lenovo IdeaPad 15"','computadoras','venta',1450,1800,'como_nuevo','Chimbote'],['Bicicleta montañera aro 26','bicicletas','ambas',520,null,'bueno','Nuevo Chimbote'],
 ['Zapatillas running talla 41','calzado','venta',90,130,'bueno','Chimbote'],['Juego de sábanas 2 plazas','hogar','intercambio',null,null,'nuevo','Huaraz'],
 ['Colección Harry Potter (7 libros)','libros','ambas',180,null,'bueno','Trujillo'],['Celular Samsung A32 128GB','celulares','venta',380,450,'bueno','Chimbote'],
 ['Cuna de madera con colchón','ninos','venta',250,null,'bueno','Nuevo Chimbote'],['Moto lineal 150cc','motos','ambas',3900,null,'bueno','Casma'],
 ['Escritorio de melamina','muebles','venta',140,180,'bueno','Chimbote'],['Casaca de cuero talla M','moda','intercambio',null,null,'como_nuevo','Chimbote']];
foreach ($datos as [$t,$c,$m,$p,$a,$e,$ub]) crear_producto($id, ['titulo'=>$t,'descripcion'=>'Producto de ejemplo para probar el catálogo de ReUns.','categoria_id'=>categoria_por_slug($c)['id'],'modalidad'=>$m,'precio'=>$p,'precio_anterior'=>$a,'estado_conservacion'=>$e,'ubicacion'=>$ub]);
echo count($datos)." productos de ejemplo cargados.\n";
