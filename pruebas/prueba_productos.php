<?php
// Pruebas del catálogo. Usa la base aparte: DB_NAME=reuns_prueba php pruebas/prueba_productos.php
if (PHP_SAPI !== 'cli') exit('Solo terminal.');
if (getenv('DB_NAME') !== 'reuns_prueba') exit("Define DB_NAME=reuns_prueba para no tocar tus datos reales.\n");
require __DIR__.'/../app/config/config.php'; require APP.'/config/db.php';
foreach (['sesion','seguridad','auditoria','correo','imagenes','paginacion'] as $a) require APP."/ayudas/$a.php";
foreach (['usuario','producto'] as $m) require APP."/modelos/$m.php"; require APP.'/controladores/auth.php'; require APP.'/controladores/productos.php';
$_SERVER['REMOTE_ADDR'] = '10.0.0.9'; $_SESSION = [];
foreach (['producto_imagenes','productos','tokens_usuario','intentos_login','auditoria','usuarios'] as $t) db()->exec("DELETE FROM $t");
$antes = ['productos' => scandir(dir_uploads('productos')), 'perfiles' => scandir(dir_uploads('perfiles'))];
$ok = 0; $fallos = 0; function t(string $n, bool $c): void { global $ok, $fallos; echo ($c ? '  ok   ' : '  FALLA ').$n."\n"; $c ? $ok++ : $fallos++; }
function usuario(string $n, bool $verif = true): array { $id = crear_usuario($n, strtolower($n).'@t.com', password_hash('Clave1234', PASSWORD_DEFAULT));
  if ($verif) db()->exec("UPDATE usuarios SET email_verificado_en=NOW() WHERE id=$id"); return usuario_por_id($id); }
function archivo(string $ruta): array { return ['name' => 'x.jpg', 'tmp_name' => $ruta, 'error' => 0, 'size' => filesize($ruta)]; }
function lista(array ...$a): array { $r = ['name' => [], 'tmp_name' => [], 'error' => [], 'size' => []]; foreach ($a as $f) foreach ($f as $k => $v) $r[$k][] = $v; return $r; }
function jpg(string $r, int $w, int $h): string { $i = imagecreatetruecolor($w, $h); imagefill($i, 0, 0, imagecolorallocate($i, 200, 100, 50)); imagejpeg($i, $r); return $r; }
$A = usuario('Ana'); $B = usuario('Beto'); $C = usuario('Carla', false);
$base = ['titulo'=>'Laptop Lenovo IdeaPad','descripcion'=>'Funciona perfecto, con cargador.','categoria_id'=>categoria_por_slug('computadoras')['id'],'modalidad'=>'venta','precio'=>'1450','estado_conservacion'=>'bueno','ubicacion'=>'Chimbote'];
$f1 = jpg('/tmp/t1.jpg', 1600, 1200); $f2 = '/tmp/t2.png'; $im = imagecreatetruecolor(300, 300); imagesavealpha($im, true); imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127)); imagepng($im, $f2);
// --- validación ---
t('rechaza título corto', isset(validar_producto(['titulo'=>'abc'] + $base)[0]['titulo']));
t('venta exige precio', isset(validar_producto(['precio'=>''] + $base)[0]['precio']));
t('rechaza precio negativo o con letras', isset(validar_producto(['precio'=>'-5'] + $base)[0]['precio']) && isset(validar_producto(['precio'=>'abc'] + $base)[0]['precio']));
t('precio anterior debe ser mayor', isset(validar_producto(['precio_anterior'=>'100'] + $base)[0]['precio_anterior']));
t('rechaza categoría inexistente', isset(validar_producto(['categoria_id'=>99999] + $base)[0]['categoria_id']));
[$e, $d] = validar_producto(['modalidad'=>'intercambio','precio'=>''] + $base); t('intercambio no pide precio', !$e && $d['precio'] === null);
// --- publicar ---
[$e,] = publicar_producto($C, $base, ['name'=>['a'],'tmp_name'=>[$f1],'error'=>[0],'size'=>[1]], false); t('correo sin verificar no puede publicar', isset($e['general']));
[$e,] = publicar_producto($A, $base, null, false); t('exige al menos una foto', isset($e['fotos']));
$n0 = count(scandir(dir_uploads('productos')));
$poli = '/tmp/poli.jpg'; copy($f1, $poli); file_put_contents($poli, '<?php system($_GET["c"]); ?>', FILE_APPEND);
file_put_contents('/tmp/evil.jpg', '<?php echo 1; ?>'); file_put_contents('/tmp/gordo.jpg', str_repeat("\0", 4 * 1048576)); $g = imagecreatetruecolor(200, 200); imagegif($g, '/tmp/a.gif');
foreach ([['PHP disfrazado de .jpg', '/tmp/evil.jpg'], ['archivo de 4 MB', '/tmp/gordo.jpg'], ['GIF', '/tmp/a.gif']] as [$n, $r]) {
  [$e, $id] = publicar_producto($A, $base, lista(archivo($r)), false); t("rechaza $n", isset($e['fotos']) && !$id); }
t('rechazos no dejan archivos ni productos', count(scandir(dir_uploads('productos'))) === $n0 && (int)db()->query('SELECT COUNT(*) FROM productos')->fetchColumn() === 0);
[$e,] = publicar_producto($A, $base, lista(...array_fill(0, 6, archivo($f1))), false); t('rechaza más de 5 fotos', isset($e['fotos']));
[$e, $pid] = publicar_producto($A, $base, lista(archivo($f1), archivo($f2)), false); t('publica con 2 fotos (JPG y PNG)', !$e && $pid > 0 && count(imagenes_de($pid)) === 2);
$ims = imagenes_de($pid); $ruta = dir_uploads('productos').'/'.$ims[0]['archivo']; [$w] = getimagesize($ruta);
t('imagen guardada como .jpg aleatorio de máx. 1200 px', str_ends_with($ims[0]['archivo'], '.jpg') && $w === 1200 && preg_match('/^[a-f0-9]{32}\.jpg$/', $ims[0]['archivo']));
[$e, $pid2] = publicar_producto($A, ['titulo'=>'Foto con código oculto'] + $base, lista(archivo($poli)), false);
t('la imagen re-codificada no conserva código PHP', !$e && !str_contains(file_get_contents(dir_uploads('productos').'/'.imagenes_de($pid2)[0]['archivo']), '<?php'));
// --- búsqueda y filtros ---
$mk = fn($t, $d, $cat, $mod, $pr, $ant, $est, $ub, $u = null) => crear_producto((int)($u ?? $A['id']), ['titulo'=>$t,'descripcion'=>$d,'categoria_id'=>categoria_por_slug($cat)['id'],'modalidad'=>$mod,'precio'=>$pr,'precio_anterior'=>$ant,'estado_conservacion'=>$est,'ubicacion'=>$ub]);
$y = $mk('Laptop Dell Inspiron', 'Buen equipo de oficina', 'computadoras', 'venta', 900, 1100, 'bueno', 'Trujillo');
$x = $mk('Mouse gamer RGB', 'Compatible con cualquier laptop', 'tecnologia', 'venta', 60, null, 'nuevo', 'Chimbote');
$s = $mk('Sábanas 2 plazas', 'Nuevas en caja', 'muebles', 'intercambio', null, null, 'nuevo', 'Huaraz');
$m = $mk('Bicicleta montañera', 'Aro 26 con cambios', 'bicicletas', 'ambas', 520, null, 'como_nuevo', 'Chimbote');
$r = buscar_productos(['q' => 'laptop']); t('búsqueda por palabra encuentra título y descripción', $r['total'] === 3);
t('relevancia: el título coincide antes que la descripción', $r['items'][0]['titulo'] === 'Laptop Dell Inspiron' || $r['items'][0]['titulo'] === 'Laptop Lenovo IdeaPad');
t('relevancia: "Mouse" (solo descripción) va al final', end($r['items'])['id'] == $x);
t('categoría padre incluye subcategorías', buscar_productos(['cat' => 'tecnologia'])['total'] === 4);
t('categoría hija filtra solo la suya', buscar_productos(['cat' => 'computadoras'])['total'] === 3);
t('modalidad venta excluye "solo intercambio"', buscar_productos(['mod' => 'venta'])['total'] === 5);
t('modalidad intercambio incluye "ambas"', buscar_productos(['mod' => 'intercambio'])['total'] === 2);
t('filtro de precio mín/máx', buscar_productos(['min' => 500, 'max' => 1000])['total'] === 2);
t('filtro por estado', buscar_productos(['est' => 'nuevo'])['total'] === 2);
t('filtro por ubicación', buscar_productos(['ubicacion' => 'trujillo'])['total'] === 1);
t('solo ofertas', buscar_productos(['oferta' => 1])['total'] === 1);
$p = array_column(buscar_productos(['mod' => 'venta', 'orden' => 'precio_asc'])['items'], 'precio'); t('orden por precio ascendente', $p === array_values(array_filter($p, 'strlen')) && (float)$p[0] <= (float)$p[1]);
t("inyección SQL en la búsqueda no rompe ni devuelve todo", buscar_productos(['q' => "' OR 1=1 --"])['total'] === 0);
t('el comodín % se trata como texto', buscar_productos(['q' => '%'])['total'] === 0);
for ($i = 1; $i <= 25; $i++) $mk("Zzpag $i", 'relleno de paginación', 'otros', 'venta', 10 + $i, null, 'bueno', null);
$r = buscar_productos(['q' => 'zzpag'], 3, 10); t('paginación: 25 resultados en 3 páginas, la última con 5', $r['total'] === 25 && $r['paginas'] === 3 && count($r['items']) === 5);
t('página fuera de rango se ajusta', buscar_productos(['q' => 'zzpag'], 99, 10)['pag'] === 3);
// --- permisos y estados ---
t('otro usuario NO puede editar', isset(editar_producto($B, $pid, $base, null, [], false)['general']));
t('otro usuario NO puede cambiar disponibilidad', cambiar_disponibilidad($pid, (int)$B['id'], 'eliminado') !== null && producto_por_id($pid) !== null);
t('otro usuario no ve el producto como propio', producto_propio($pid, (int)$B['id']) === null);
t('el dueño puede editar', editar_producto($A, $pid, ['titulo'=>'Laptop Lenovo (rebajada)'] + $base, null, [], false) === [] && producto_por_id($pid)['titulo'] === 'Laptop Lenovo (rebajada)');
$otra = (int)imagenes_de($pid2)[0]['id']; editar_producto($A, $pid, $base, null, [$otra], false); t('no se puede quitar la foto de otro producto', count(imagenes_de($pid2)) === 1);
$e = editar_producto($A, $pid, $base, null, array_column(imagenes_de($pid), 'id'), false); t('no deja el producto sin fotos', isset($e['fotos']));
$e = editar_producto($A, $pid, $base, lista(archivo($f1)), [(int)imagenes_de($pid)[0]['id']], false); t('quitar una foto y agregar otra', $e === [] && count(imagenes_de($pid)) === 2);
cambiar_disponibilidad($pid, (int)$A['id'], 'pausado'); t('pausado sale del catálogo', !in_array($pid, array_column(buscar_productos(['q' => 'lenovo'])['items'], 'id')));
cambiar_disponibilidad($pid, (int)$A['id'], 'activo'); t('reactivado vuelve al catálogo', in_array($pid, array_column(buscar_productos(['q' => 'lenovo'])['items'], 'id')));
t('"vendido" no aplica a solo intercambio', cambiar_disponibilidad($s, (int)$A['id'], 'vendido') !== null);
cambiar_disponibilidad($pid, (int)$A['id'], 'vendido'); t('vendido sale del catálogo y va al historial', buscar_productos(['q' => 'lenovo'])['total'] === 0 && in_array($pid, array_column(productos_de_usuario((int)$A['id'], 'historial'), 'id')));
cambiar_disponibilidad($m, (int)$A['id'], 'eliminado'); t('eliminado desaparece por completo', producto_por_id($m) === null);
db()->exec("UPDATE usuarios SET estado='suspendido' WHERE id=".(int)$A['id']); t('productos de cuenta suspendida se ocultan', buscar_productos([])['total'] === 0);
db()->exec("UPDATE usuarios SET estado='activo' WHERE id=".(int)$A['id']);
// --- límite de publicaciones y foto de perfil ---
$D = usuario('Dora'); for ($i = 0; $i < 10; $i++) $mk("Relleno $i", 'descripcion de relleno', 'otros', 'venta', 5, null, 'bueno', null, (int)$D['id']);
[$e,] = publicar_producto($D, $base, lista(archivo($f1)), false); t('límite de 10 publicaciones por hora', isset($e['general']));
[$fa, $er] = guardar_imagen(archivo($f1), 'perfiles', 400, true, false); [$aw, $ah] = getimagesize(dir_uploads('perfiles').'/'.$fa); t('foto de perfil recortada a cuadrado 400×400', !$er && $aw === 400 && $ah === 400);
// limpieza: borra solo lo creado por esta prueba
foreach (['productos', 'perfiles'] as $d) foreach (array_diff(scandir(dir_uploads($d)), $antes[$d]) as $f) @unlink(dir_uploads($d)."/$f");
@unlink('/tmp/t1.jpg'); @unlink('/tmp/t2.png'); @unlink($poli); @unlink('/tmp/evil.jpg'); @unlink('/tmp/gordo.jpg'); @unlink('/tmp/a.gif');
echo "\n$ok correctas, $fallos fallidas\n"; exit($fallos ? 1 : 0);
