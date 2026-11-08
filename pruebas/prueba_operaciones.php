<?php
/*
 * Pruebas de la Etapa 5 (ofertas, compras, intercambios). Uso, en el Shell de XAMPP:
 *   set DB_NAME=reuns_prueba
 *   php pruebas\prueba_operaciones.php
 * La base debe tener el esquema + etapa4b.sql + etapa5.sql. Por seguridad SOLO corre contra una base cuyo nombre termine en "_prueba":
 * borra productos, ofertas e intercambios de esa base.
 */
require __DIR__ . '/../app/config/config.php';
require APP . '/config/db.php';
require APP . '/modelos/usuario.php';
require APP . '/modelos/producto.php';
require APP . '/modelos/operacion.php';

if (!str_ends_with((string)env('DB_NAME', 'reuns'), '_prueba')) exit("Detenido: usa una base que termine en _prueba (set DB_NAME=reuns_prueba).\n");

// Modo hijo (para la prueba de concurrencia): php prueba_operaciones.php --hijo <oferta_id> <usuario_id>
if (($argv[1] ?? '') === '--hijo') { echo gestionar_oferta((int)$argv[2], (int)$argv[3], 'aceptar') ?? 'OK'; exit; }

$ok = 0; $fallos = 0;
function t(string $n, bool $c): void { global $ok, $fallos; echo ($c ? 'OK     ' : 'FALLA  ') . $n . PHP_EOL; $c ? $ok++ : $fallos++; }
function q(string $sql, array $p = []) { $s = db()->prepare($sql); $s->execute($p); return $s; }
function estado_of(int $id): string { return (string)q('SELECT estado FROM ofertas WHERE id=?', [$id])->fetchColumn(); }
function estado_in(int $id): string { return (string)q('SELECT estado FROM intercambios WHERE id=?', [$id])->fetchColumn(); }
function estado_pr(int $id): string { return (string)q('SELECT estado FROM productos WHERE id=?', [$id])->fetchColumn(); }
function nuevo_producto(int $uid, string $titulo, string $mod, float $precio): int {
    q("INSERT INTO productos (usuario_id,titulo,descripcion,categoria,estado_producto,modalidad,precio,ubicacion) VALUES (?,?,?,?,?,?,?,?)",
      [$uid, $titulo, 'Descripción de prueba', 'otros', 'bueno', $mod, $precio, 'Chimbote']);
    return (int)db()->lastInsertId();
}
function nuevo_usuario(string $n, bool $verificado = true): int {
    q("INSERT INTO usuarios (nombre,email,clave_hash,email_verificado_en) VALUES (?,?,?,?)", [$n, 't_' . strtolower($n) . '@uns.edu.pe', 'x', $verificado ? date('Y-m-d H:i:s') : null]);
    return (int)db()->lastInsertId();
}

// --- Limpieza y datos ---
db()->exec('SET FOREIGN_KEY_CHECKS=0');
foreach (['historial_operaciones', 'intercambios', 'ofertas', 'producto_imagenes', 'productos'] as $tb) db()->exec("DELETE FROM $tb");
db()->exec("DELETE FROM usuarios WHERE email LIKE 't\\_%@uns.edu.pe'");
db()->exec('SET FOREIGN_KEY_CHECKS=1');
$ana = nuevo_usuario('Ana'); $beto = nuevo_usuario('Beto', false); $carla = nuevo_usuario('Carla'); $dani = nuevo_usuario('Dani');
$laptop = nuevo_producto($ana, 'Laptop HP', 'venta', 1200);
$polera = nuevo_producto($ana, 'Polera', 'ambas', 0);
$iphone = nuevo_producto($ana, 'iPhone', 'venta', 900);
$libro = nuevo_producto($beto, 'Libro de cálculo', 'intercambio', 0);
$bici = nuevo_producto($beto, 'Bici', 'venta', 300);
$zapas = nuevo_producto($carla, 'Zapatillas', 'intercambio', 0);

echo "== Compras y ofertas ==\n";
[$id, $err] = crear_solicitud($laptop, $beto, 'compra', '', '');
t('usuario sin correo verificado no puede operar', $id === null && str_contains($err, 'Verifica tu correo'));
q('UPDATE usuarios SET email_verificado_en = NOW() WHERE id = ?', [$beto]);
[$ofBeto, $err] = crear_solicitud($laptop, $beto, 'compra', '999', 'Hola, me interesa');
t('compra al precio publicado (ignora el monto enviado)', $ofBeto > 0 && (float)q('SELECT monto FROM ofertas WHERE id=?', [$ofBeto])->fetchColumn() === 1200.0);
[$id, $err] = crear_solicitud($laptop, $beto, 'oferta', '1000', '');
t('no se permite una segunda solicitud viva del mismo comprador', $id === null);
[$id, $err] = crear_solicitud($laptop, $ana, 'compra', '', '');
t('el dueño no puede comprar su producto', $id === null && str_contains($err, 'propio'));
[$id, $err] = crear_solicitud($libro, $carla, 'compra', '', '');
t('producto solo intercambio no admite compra', $id === null && str_contains($err, 'solo para intercambio'));
[$id, $err] = crear_solicitud($polera, $carla, 'oferta', '10', '');
t('producto "ambas" sin precio no admite oferta', $id === null);
[$id, $err] = crear_solicitud($laptop, $carla, 'oferta', 'abc', '');
t('monto no numérico rechazado', $id === null && str_contains($err, 'monto válido'));
[$id, $err] = crear_solicitud($laptop, $carla, 'oferta', '-5', '');
t('monto negativo rechazado', $id === null);
[$id, $err] = crear_solicitud($laptop, $carla, 'oferta', '1300', '');
t('oferta mayor al precio rechazada', $id === null && str_contains($err, 'no puede superar'));
[$id, $err] = crear_solicitud($laptop, $carla, 'oferta', '1000', str_repeat('x', 301));
t('mensaje de más de 300 caracteres rechazado', $id === null);
[$ofCarla, $err] = crear_solicitud($laptop, $carla, 'oferta', '1000,50', "Línea 1\nLínea 2 <script>");
t('oferta válida con coma decimal', $ofCarla > 0 && (float)q('SELECT monto FROM ofertas WHERE id=?', [$ofCarla])->fetchColumn() === 1000.50);
t('contador de pendientes del vendedor = 2', contar_pendientes_recibidas($ana) === 2);

[$id, $err] = crear_solicitud(99999, $carla, 'compra', '', '');
t('producto inexistente', $id === null);
t('un extraño no ve la operación', gestionar_oferta($ofBeto, $dani, 'cancelar') === 'No encontramos esa operación.');
t('el comprador no puede aceptar', str_contains((string)gestionar_oferta($ofBeto, $beto, 'aceptar'), 'Solo el vendedor'));
t('acción inventada rechazada', gestionar_oferta($ofBeto, $ana, 'borrar') === 'Acción no válida.');

echo "== Aceptar, reservar, confirmar ==\n";
t('el vendedor acepta la oferta de Carla', gestionar_oferta($ofCarla, $ana, 'aceptar') === null && estado_of($ofCarla) === 'aceptada');
t('la solicitud pendiente de Beto se rechazó sola', estado_of($ofBeto) === 'rechazada');
t('el rechazo automático quedó en el historial', (int)q("SELECT COUNT(*) FROM historial_operaciones WHERE tipo='oferta' AND operacion_id=? AND accion='rechazo_automatico' AND usuario_id IS NULL", [$ofBeto])->fetchColumn() === 1);
t('producto reservado', producto_reservado($laptop));
[$id, $err] = crear_solicitud($laptop, $dani, 'compra', '', '');
t('no se aceptan solicitudes nuevas sobre producto reservado', $id === null && str_contains($err, 'operación aceptada'));
t('no se puede marcar vendido a mano un producto reservado', str_contains((string)cambiar_disponibilidad($laptop, $ana, 'vendido'), 'operación aceptada'));
t('no se puede eliminar un producto reservado', str_contains((string)cambiar_disponibilidad($laptop, $ana, 'eliminado'), 'operación aceptada'));
t('no se puede aceptar dos veces', gestionar_oferta($ofCarla, $ana, 'aceptar') !== null);
t('confirma el comprador: aún no se completa', gestionar_oferta($ofCarla, $carla, 'confirmar') === null && estado_of($ofCarla) === 'aceptada');
t('no puede confirmar dos veces y completar sola', estado_of($ofCarla) === 'aceptada' && gestionar_oferta($ofCarla, $carla, 'confirmar') === null && estado_of($ofCarla) === 'aceptada');
t('confirma el vendedor: se completa', gestionar_oferta($ofCarla, $ana, 'confirmar') === null && estado_of($ofCarla) === 'completada');
t('el producto pasó a vendido', estado_pr($laptop) === 'vendido');
t('no se puede volver a publicar un producto con operación completada', str_contains((string)cambiar_disponibilidad($laptop, $ana, 'activo'), 'operación completada'));
t('una operación completada no se puede cancelar', gestionar_oferta($ofCarla, $carla, 'cancelar') !== null);

echo "== Cancelar libera el producto ==\n";
[$o1, ] = crear_solicitud($bici, $ana, 'oferta', '250', '');
t('Beto acepta la oferta de Ana por la bici', gestionar_oferta($o1, $beto, 'aceptar') === null && producto_reservado($bici));
t('Ana cancela una operación aceptada', gestionar_oferta($o1, $ana, 'cancelar') === null && estado_of($o1) === 'cancelada');
t('el producto vuelve a estar libre', !producto_reservado($bici) && estado_pr($bici) === 'activo');
[$o2, $err] = crear_solicitud($bici, $ana, 'oferta', '260', '');
t('tras cancelar, Ana puede volver a ofertar', $o2 > 0);
t('el vendedor rechaza', gestionar_oferta($o2, $beto, 'rechazar') === null && estado_of($o2) === 'rechazada');
t('lo rechazado no se puede aceptar después', gestionar_oferta($o2, $beto, 'aceptar') !== null);

echo "== Cerrar la publicación rechaza lo pendiente ==\n";
[$o3, ] = crear_solicitud($iphone, $carla, 'compra', '', '');
[$o4, ] = crear_solicitud($iphone, $dani, 'oferta', '800', '');
t('el dueño marca vendido el iPhone', cambiar_disponibilidad($iphone, $ana, 'vendido') === null);
t('las dos solicitudes pendientes se rechazaron', estado_of($o3) === 'rechazada' && estado_of($o4) === 'rechazada');

echo "== Intercambios ==\n";
[$id, $err] = proponer_intercambio($libro, $ana, $bici, '');
t('no se ofrece un producto ajeno', $id === null && str_contains($err, 'tuyos'));
$soloVenta = nuevo_producto($ana, 'Mouse', 'venta', 50);
[$id, $err] = proponer_intercambio($libro, $ana, $soloVenta, '');
t('el producto ofrecido debe admitir intercambio', $id === null && str_contains($err, 'admitir intercambio'));
[$id, $err] = proponer_intercambio($bici, $ana, $polera, '');
t('no se pide un producto solo de venta', $id === null && str_contains($err, 'solo para venta'));
[$id, $err] = proponer_intercambio($polera, $ana, $polera, '');
t('no se intercambia un producto por sí mismo', $id === null);
[$id, $err] = proponer_intercambio($polera, $beto, $libro, '');
// polera es de Ana, libro es de Beto: válido
t('Beto propone su libro por la polera de Ana', $id > 0); $inBeto = $id;
[$id, $err] = proponer_intercambio($polera, $beto, $libro, '');
t('propuesta duplicada rechazada', $id === null);
[$inCarla, $err] = proponer_intercambio($polera, $carla, $zapas, 'Te ofrezco mis zapatillas');
t('Carla propone otra cosa por la misma polera', $inCarla > 0);
$ofrecibles = array_column(productos_para_intercambio($beto), 'id');
t('lista de ofrecibles: solo productos activos con intercambio', in_array($libro, $ofrecibles) && !in_array($bici, $ofrecibles));
t('quien propone no puede aceptar', str_contains((string)gestionar_intercambio($inBeto, $beto, 'aceptar'), 'Solo quien recibe'));
t('Ana acepta la propuesta de Beto', gestionar_intercambio($inBeto, $ana, 'aceptar') === null && estado_in($inBeto) === 'aceptado');
t('la propuesta de Carla se rechazó sola', estado_in($inCarla) === 'rechazado');
t('ambos productos quedan reservados', producto_reservado($polera) && producto_reservado($libro));
[$id, $err] = proponer_intercambio($libro, $dani, nuevo_producto($dani, 'Casaca', 'intercambio', 0), '');
t('no se puede pedir un producto ya comprometido', $id === null && str_contains($err, 'operación aceptada'));
t('Ana confirma; aún no se completa', gestionar_intercambio($inBeto, $ana, 'confirmar') === null && estado_in($inBeto) === 'aceptado');
t('Beto confirma; se completa', gestionar_intercambio($inBeto, $beto, 'confirmar') === null && estado_in($inBeto) === 'completado');
t('ambos productos pasan a intercambiado', estado_pr($polera) === 'intercambiado' && estado_pr($libro) === 'intercambiado');
[$id, $err] = proponer_intercambio($polera, $carla, $zapas, '');
t('no se propone por un producto ya intercambiado', $id === null);

echo "== Candados de la base de datos ==\n";
$x = nuevo_producto($ana, 'Producto candado', 'venta', 100);
q("INSERT INTO ofertas (producto_id,comprador_id,vendedor_id,tipo,monto,estado,activa,aceptada_producto) VALUES (?,?,?,?,?,?,?,?)", [$x, $beto, $ana, 'compra', 100, 'aceptada', 1, $x]);
try { q("INSERT INTO ofertas (producto_id,comprador_id,vendedor_id,tipo,monto,estado,activa,aceptada_producto) VALUES (?,?,?,?,?,?,?,?)", [$x, $carla, $ana, 'compra', 100, 'aceptada', 1, $x]); $dup = false; } catch (PDOException $e) { $dup = $e->getCode() === '23000'; }
t('la BD impide dos operaciones aceptadas sobre el mismo producto', $dup);
try { q("INSERT INTO ofertas (producto_id,comprador_id,vendedor_id,tipo,monto,estado,activa) VALUES (?,?,?,?,?,?,?)", [$x, $beto, $ana, 'oferta', 90, 'pendiente', 1]); $dup = false; } catch (PDOException $e) { $dup = $e->getCode() === '23000'; }
t('la BD impide dos solicitudes vivas del mismo comprador', $dup);

echo "== Antispam ==\n";
for ($k = 0; $k < OP_MAX_HORA; $k++) q("INSERT INTO ofertas (producto_id,comprador_id,vendedor_id,tipo,monto,estado,activa) VALUES (?,?,?,?,?,?,NULL)", [$x, $dani, $ana, 'oferta', 10, 'cancelada']);
[$id, $err] = crear_solicitud($bici, $dani, 'compra', '', '');
t('límite de solicitudes por hora', $id === null && str_contains($err, 'muchas solicitudes'));

echo "== Concurrencia: dos aceptaciones a la vez ==\n";
$y = nuevo_producto($ana, 'Producto carrera', 'venta', 100);
[$c1, ] = crear_solicitud($y, $beto, 'compra', '', '');
[$c2, ] = crear_solicitud($y, $carla, 'compra', '', '');
$proc = [];
foreach ([$c1, $c2] as $k => $oid) {
    $proc[$k] = proc_open([PHP_BINARY, __FILE__, '--hijo', (string)$oid, (string)$ana], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes[$k], null, ['DB_NAME' => env('DB_NAME'), 'PATH' => getenv('PATH')] + $_ENV);
}
$salidas = [];
foreach ($proc as $k => $p) { $salidas[$k] = stream_get_contents($pipes[$k][1]); proc_close($p); }
$aceptadas = (int)q("SELECT COUNT(*) FROM ofertas WHERE producto_id = ? AND estado = 'aceptada'", [$y])->fetchColumn();
t('exactamente UNA queda aceptada (' . implode(' / ', array_map('trim', $salidas)) . ')', $aceptadas === 1);
t('la otra quedó rechazada', (int)q("SELECT COUNT(*) FROM ofertas WHERE producto_id = ? AND estado = 'rechazada'", [$y])->fetchColumn() === 1);

echo "== Listados y vistas ==\n";
$rec = ofertas_de($ana, 'recibidas', 'cerradas');
t('listado normalizado de ofertas recibidas (cerradas)', count($rec) >= 3 && isset($rec[0]['p']['titulo'], $rec[0]['otro']));
$h = historial_de($carla, 'compras');
t('historial: filtro "compras" de Carla = la laptop', count($h) === 1 && $h[0]['p']['titulo'] === 'Laptop HP' && $h[0]['estado'] === 'completada');
t('historial: filtro "ventas" de Ana incluye la laptop', count(array_filter(historial_de($ana, 'ventas'), fn($z) => $z['p']['id'] === $laptop)) === 1);
$hi = historial_de($beto, 'intercambios');
t('historial: intercambio completado con datos del producto ofrecido', count($hi) >= 1 && isset($hi[0]['q']['titulo']));
$cam = cambios_de('oferta', [$ofCarla]);
t('el historial registra cada cambio con fecha', count($cam[$ofCarla]) >= 4 && !empty($cam[$ofCarla][0]['creado_en']));
$op = ofertas_de($ana, 'recibidas', 'cerradas')[0];
t('una operación terminada no ofrece acciones', acciones_disponibles($op) === []);
$ab = ['tipo' => 'oferta', 'estado' => 'pendiente', 'soy' => 'vendedor', 'yo_confirme' => false];
t('vendedor ve Aceptar/Rechazar en pendiente', array_column(acciones_disponibles($ab), 0) === ['aceptar', 'rechazar']);
$ab = ['tipo' => 'intercambio', 'estado' => 'aceptado', 'soy' => 'solicitante', 'yo_confirme' => false];
t('solicitante ve Confirmar/Cancelar en aceptado', array_column(acciones_disponibles($ab), 0) === ['confirmar', 'cancelar']);

echo "\n$ok correctas, $fallos fallidas\n";
exit($fallos ? 1 : 0);
