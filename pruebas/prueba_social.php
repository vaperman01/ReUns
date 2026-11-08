<?php
/*
 * Pruebas de la Etapa 6 (favoritos, chat, notificaciones). Uso, en el Shell de XAMPP:
 *   set DB_NAME=reuns_prueba
 *   php pruebas\prueba_social.php
 * La base debe tener esquema + etapa4b + etapa5 + etapa6. SOLO corre contra una base que termine en "_prueba"
 * (borra productos, mensajes, favoritos, notificaciones y operaciones de esa base).
 */
require __DIR__ . '/../app/config/config.php';
require APP . '/config/db.php';
require APP . '/modelos/usuario.php';
require APP . '/modelos/producto.php';
require APP . '/modelos/operacion.php';
require APP . '/modelos/notificacion.php';
require APP . '/modelos/favorito.php';
require APP . '/modelos/chat.php';

if (!str_ends_with((string)env('DB_NAME', 'reuns'), '_prueba')) exit("Detenido: usa una base que termine en _prueba (set DB_NAME=reuns_prueba).\n");

$ok = 0; $fallos = 0;
function t(string $n, bool $c): void { global $ok, $fallos; echo ($c ? 'OK     ' : 'FALLA  ') . $n . PHP_EOL; $c ? $ok++ : $fallos++; }
function q(string $sql, array $p = []) { $s = db()->prepare($sql); $s->execute($p); return $s; }
function nuevo_producto(int $uid, string $titulo, string $mod = 'venta', float $precio = 100): int {
    q("INSERT INTO productos (usuario_id,titulo,descripcion,categoria,estado_producto,modalidad,precio,ubicacion) VALUES (?,?,?,?,?,?,?,?)", [$uid, $titulo, 'Descripción', 'otros', 'bueno', $mod, $precio, 'Chimbote']);
    return (int)db()->lastInsertId();
}
function nuevo_usuario(string $n, bool $verificado = true): int {
    q("INSERT INTO usuarios (nombre,email,clave_hash,email_verificado_en) VALUES (?,?,?,?)", [$n, 't_' . strtolower($n) . '@uns.edu.pe', 'x', $verificado ? date('Y-m-d H:i:s') : null]);
    return (int)db()->lastInsertId();
}

db()->exec('SET FOREIGN_KEY_CHECKS=0');
foreach (['notificaciones', 'mensajes', 'conversaciones', 'favoritos', 'historial_operaciones', 'intercambios', 'ofertas', 'producto_imagenes', 'productos'] as $tb) db()->exec("DELETE FROM $tb");
db()->exec("DELETE FROM usuarios WHERE email LIKE 't\\_%@uns.edu.pe'");
db()->exec('SET FOREIGN_KEY_CHECKS=1');
$ana = nuevo_usuario('Ana'); $beto = nuevo_usuario('Beto'); $carla = nuevo_usuario('Carla'); $dani = nuevo_usuario('Dani', false);
$laptop = nuevo_producto($ana, 'Laptop HP', 'venta', 1200);
$bici = nuevo_producto($ana, 'Bici', 'venta', 300);
$libro = nuevo_producto($beto, 'Libro', 'ambas', 20);
q('INSERT INTO producto_imagenes (producto_id, archivo) VALUES (?,?)', [$laptop, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.jpg']);

echo "== Favoritos ==\n";
t('guardar un favorito', agregar_favorito($beto, $laptop) === null && es_favorito($beto, $laptop));
t('guardarlo otra vez no duplica ni da error', agregar_favorito($beto, $laptop) === null && (int)q('SELECT COUNT(*) FROM favoritos WHERE usuario_id=? AND producto_id=?', [$beto, $laptop])->fetchColumn() === 1);
t('la BD impide duplicados aunque se salte el PHP', (function () use ($beto, $laptop) { try { q('INSERT INTO favoritos (usuario_id, producto_id) VALUES (?,?)', [$beto, $laptop]); return false; } catch (PDOException $e) { return $e->getCode() === '23000'; } })());
t('no se guarda el propio producto', str_contains((string)agregar_favorito($ana, $laptop), 'tu propio'));
t('producto inexistente', agregar_favorito($beto, 99999) === 'Ese producto no existe.');
agregar_favorito($beto, $bici);
$f = favoritos_de($beto);
t('listado con imagen, título y estado (como el catálogo)', count($f) === 2 && isset($f[0]['titulo'], $f[0]['estado'], $f[0]['precio']) && in_array('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.jpg', array_column($f, 'imagen'), true));
t('los favoritos de otro usuario no se mezclan', count(favoritos_de($carla)) === 0);
q("UPDATE productos SET estado='vendido' WHERE id=?", [$bici]);
t('un favorito vendido sigue en la lista, identificado por su estado', in_array('vendido', array_column(favoritos_de($beto), 'estado'), true));
q("UPDATE productos SET estado='eliminado' WHERE id=?", [$bici]);
t('un favorito eliminado desaparece de la lista', count(favoritos_de($beto)) === 1);
quitar_favorito($beto, $laptop);
t('quitar favorito', !es_favorito($beto, $laptop) && count(favoritos_de($beto)) === 0);
quitar_favorito($beto, $laptop);
t('quitar uno que no existe no falla', true);

echo "== Notificaciones ==\n";
notificar($ana, 'operacion', 'Prueba', '/paginas/operaciones.php?ver=recibidas');
t('notificar crea una no leída', contar_notificaciones_no_leidas($ana) === 1);
notificar($ana, 'sistema', 'Enlace externo', 'https://malo.example/robo');
$ext = q("SELECT url FROM notificaciones WHERE usuario_id=? AND texto='Enlace externo'", [$ana])->fetchColumn();
t('un enlace externo NO se guarda', $ext === null || $ext === false);
notificar($ana, 'sistema', 'Otro intento', '/paginas/../admin/x.php');
t('un enlace con ../ NO se guarda', q("SELECT url FROM notificaciones WHERE usuario_id=? AND texto='Otro intento'", [$ana])->fetchColumn() === null);
notificar($ana, 'tipo_raro', str_repeat('x', 400));
$r = q("SELECT tipo, CHAR_LENGTH(texto) AS n FROM notificaciones WHERE usuario_id=? ORDER BY id DESC LIMIT 1", [$ana])->fetch();
t('tipo desconocido pasa a "sistema" y el texto se recorta a 160', $r['tipo'] === 'sistema' && (int)$r['n'] === 160);
$id = (int)q('SELECT id FROM notificaciones WHERE usuario_id=? ORDER BY id LIMIT 1', [$ana])->fetchColumn();
t('nadie puede marcar como leída la notificación de otro', marcar_notificacion_leida($id, $beto) === false && contar_notificaciones_no_leidas($ana) === 4);
t('el dueño la marca como leída', marcar_notificacion_leida($id, $ana) === true && contar_notificaciones_no_leidas($ana) === 3);
t('marcar todas', marcar_todas_leidas($ana) === 3 && contar_notificaciones_no_leidas($ana) === 0);
t('listado ordenado de más reciente a más antigua', (function () use ($ana) { $l = notificaciones_de($ana); return count($l) === 4 && $l[0]['id'] > $l[3]['id']; })());

echo "== Chat: iniciar y permisos ==\n";
q("DELETE FROM notificaciones");
[$id, $err] = iniciar_conversacion($laptop, $dani);
t('usuario sin correo verificado no puede chatear', $id === null && str_contains($err, 'Verifica tu correo'));
[$id, $err] = iniciar_conversacion($laptop, $ana);
t('no se chatea con uno mismo', $id === null && str_contains($err, 'contigo mismo'));
[$id, $err] = iniciar_conversacion(99999, $beto);
t('producto inexistente', $id === null);
[$cBeto, $err] = iniciar_conversacion($laptop, $beto);
t('Beto inicia conversación sobre la laptop', $cBeto > 0);
[$otra, ] = iniciar_conversacion($laptop, $beto);
t('iniciarla de nuevo reutiliza la misma (no duplica)', $otra === $cBeto);
[$cCarla, ] = iniciar_conversacion($laptop, $carla);
t('otro comprador tiene su propia conversación', $cCarla > 0 && $cCarla !== $cBeto);
$c = conversacion_de($cBeto, $ana);
t('la conversación está ligada al producto y muestra quién es el otro', $c && (int)$c['producto_id'] === $laptop && $c['otro_nombre'] === 'Beto' && $c['soy'] === 'vendedor');
t('un tercero NO puede abrir la conversación', conversacion_de($cBeto, $carla) === null);
t('un tercero NO puede leer mensajes', mensajes_de($cBeto, $carla) === null);
[$id, $err] = enviar_mensaje($cBeto, $carla, 'Hola, intruso');
t('un tercero NO puede enviar mensajes', $id === null && str_contains($err, 'No encontramos'));
t('la BD no recibió nada del intruso', (int)q('SELECT COUNT(*) FROM mensajes WHERE remitente_id=?', [$carla])->fetchColumn() === 0);

echo "== Chat: mensajes ==\n";
[$id, $err] = enviar_mensaje($cBeto, $beto, '   ');
t('mensaje vacío rechazado', $id === null && str_contains($err, 'Escribe'));
[$id, $err] = enviar_mensaje($cBeto, $beto, str_repeat('a', 1001));
t('mensaje de más de 1000 caracteres rechazado', $id === null);
[$m1, ] = enviar_mensaje($cBeto, $beto, "\x00Hola, ¿sigue disponible?\x07");
[$m2, ] = enviar_mensaje($cBeto, $beto, "<script>alert(1)</script>\nSegunda línea");
t('se guardan en la BD', $m1 > 0 && $m2 > $m1);
t('se quitan caracteres de control, se conserva el texto', q('SELECT texto FROM mensajes WHERE id=?', [$m1])->fetchColumn() === 'Hola, ¿sigue disponible?');
t('el HTML se guarda tal cual (se escapa al mostrar), con saltos de línea', str_contains((string)q('SELECT texto FROM mensajes WHERE id=?', [$m2])->fetchColumn(), "<script>alert(1)</script>\nSegunda"));
t('el vendedor tiene 2 mensajes sin leer', contar_mensajes_no_leidos($ana) === 2 && contar_mensajes_no_leidos($beto) === 0);
t('conversaciones_de muestra no leídos y último mensaje', (function () use ($ana, $cBeto) { foreach (conversaciones_de($ana) as $l) if ((int)$l['id'] === $cBeto) return (int)$l['no_leidos'] === 2 && str_contains($l['ultimo_texto'], 'Segunda'); return false; })());
t('2 mensajes seguidos = UNA notificación sin leer para el vendedor', (int)q("SELECT COUNT(*) FROM notificaciones WHERE usuario_id=? AND tipo='mensaje' AND leida_en IS NULL", [$ana])->fetchColumn() === 1);
t('el remitente no se notifica a sí mismo', contar_notificaciones_no_leidas($beto) === 0);
$ms = mensajes_de($cBeto, $ana, 0);
t('el vendedor ve los mensajes en orden cronológico e identifica remitente', count($ms) === 2 && $ms[0]['id'] === $m1 && $ms[1]['id'] === $m2 && $ms[0]['mio'] === false);
t('al abrir el chat los no leídos bajan a 0', contar_mensajes_no_leidos($ana) === 0);
t('y la notificación de mensajes queda leída', (int)q("SELECT COUNT(*) FROM notificaciones WHERE usuario_id=? AND tipo='mensaje' AND leida_en IS NULL", [$ana])->fetchColumn() === 0);
[$m3, ] = enviar_mensaje($cBeto, $ana, 'Sí, está disponible');
$nuevos = mensajes_de($cBeto, $beto, $m2);
t('«desde» devuelve solo los mensajes posteriores', count($nuevos) === 1 && $nuevos[0]['id'] === $m3 && $nuevos[0]['mio'] === false);
t('el comprador recibe notificación de la respuesta', (int)q("SELECT COUNT(*) FROM notificaciones WHERE usuario_id=? AND tipo='mensaje'", [$beto])->fetchColumn() >= 1);
t('Beto no ve mensajes de la conversación de Carla', mensajes_de($cCarla, $beto) === null);

echo "== Chat: límites y estados ==\n";
for ($k = 0; $k < CHAT_MAX_MIN; $k++) q('INSERT INTO mensajes (conversacion_id, remitente_id, texto) VALUES (?,?,?)', [$cCarla, $carla, 'spam ' . $k]);
[$id, $err] = enviar_mensaje($cCarla, $carla, 'uno más');
t('límite de mensajes por minuto', $id === null && str_contains($err, 'muy rápido'));
q("UPDATE usuarios SET estado='suspendido' WHERE id=?", [$ana]);
[$id, $err] = enviar_mensaje($cBeto, $beto, 'hola?');
t('no se escribe a una cuenta suspendida', $id === null && str_contains($err, 'ya no tiene la cuenta activa'));
q("UPDATE usuarios SET estado='activo' WHERE id=?", [$ana]);
q("UPDATE productos SET estado='vendido' WHERE id=?", [$laptop]);
[$id, $err] = enviar_mensaje($cBeto, $beto, 'Gracias por todo');
t('en un producto vendido la conversación existente sigue funcionando', $id > 0);
[$id, $err] = iniciar_conversacion($laptop, $carla);
t('una conversación ya existente se reabre aunque el producto esté vendido', $id === $cCarla);
[$id, $err] = iniciar_conversacion($laptop, $beto);
t('(mismo caso para Beto)', $id === $cBeto);
$libro2 = nuevo_producto($ana, 'Mouse', 'venta', 50);
q("UPDATE productos SET estado='vendido' WHERE id=?", [$libro2]);
[$id, $err] = iniciar_conversacion($libro2, $beto);
t('NO se inicia una conversación nueva sobre un producto vendido', $id === null && str_contains($err, 'no está disponible'));
q("UPDATE productos SET estado='eliminado' WHERE id=?", [$laptop]);
[$id, $err] = enviar_mensaje($cBeto, $beto, 'ya no existe');
t('en una publicación eliminada ya no se escribe', $id === null && str_contains($err, 'eliminada'));

echo "== Integración con operaciones ==\n";
q("DELETE FROM notificaciones");
$camara = nuevo_producto($ana, 'Cámara', 'venta', 500);
[$of, ] = crear_solicitud($camara, $beto, 'oferta', '400', 'Hola');
$n = q("SELECT tipo, url FROM notificaciones WHERE usuario_id=?", [$ana])->fetch();
t('una nueva oferta notifica al vendedor (tipo operación, enlace a Operaciones)', $n && $n['tipo'] === 'operacion' && $n['url'] === '/paginas/operaciones.php?ver=recibidas');
gestionar_oferta($of, $ana, 'aceptar');
t('aceptar la oferta notifica al comprador', (int)q("SELECT COUNT(*) FROM notificaciones WHERE usuario_id=? AND tipo='operacion'", [$beto])->fetchColumn() === 1);
gestionar_oferta($of, $beto, 'confirmar');
t('confirmar notifica a la otra parte', (int)q("SELECT COUNT(*) FROM notificaciones WHERE usuario_id=? AND texto LIKE '%confirm%'", [$ana])->fetchColumn() === 1);
[$o2, ] = crear_solicitud(nuevo_producto($ana, 'Teclado', 'venta', 80), $beto, 'compra', '', '');
q("DELETE FROM notificaciones");
gestionar_oferta($o2, $ana, 'aceptar'); gestionar_oferta($o2, $beto, 'cancelar');
t('cancelar una operación notifica a la otra parte', (int)q("SELECT COUNT(*) FROM notificaciones WHERE usuario_id=?", [$ana])->fetchColumn() === 1);

echo "\n$ok correctas, $fallos fallidas\n";
exit($fallos ? 1 : 0);
