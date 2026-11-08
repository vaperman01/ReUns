<?php
// Pruebas de autenticación. Usa una base APARTE: crea "reuns_prueba" con base_datos/esquema.sql (cambiando el nombre)
// y ejecuta:  DB_NAME=reuns_prueba php pruebas/prueba_auth.php   (en Windows: set DB_NAME=reuns_prueba)
if (PHP_SAPI !== 'cli') exit('Solo terminal.');
if (getenv('DB_NAME') !== 'reuns_prueba') exit("Define DB_NAME=reuns_prueba para no tocar tus datos reales.\n");
require __DIR__.'/../app/config/config.php'; require APP.'/config/db.php';
foreach (['sesion','seguridad','auditoria','correo'] as $a) require APP."/ayudas/$a.php";
require APP.'/modelos/usuario.php'; require APP.'/controladores/auth.php';
putenv('MAIL_MODO=archivo'); $_SERVER['REMOTE_ADDR'] = '10.0.0.9'; $_SESSION = [];
array_map('unlink', glob(APP.'/../storage/correos/*.txt'));
foreach (['producto_imagenes','productos','tokens_usuario','intentos_login','auditoria','usuarios'] as $t) db()->exec("DELETE FROM $t");
$ok = 0; $fallos = 0;
function t(string $n, bool $c): void { global $ok, $fallos; echo ($c ? '  ok   ' : '  FALLA ').$n."\n"; $c ? $ok++ : $fallos++; }
function ultimo_token(string $pagina): string { // busca el correo más reciente que contenga el enlace de esa página
  $f = glob(APP.'/../storage/correos/*.txt'); usort($f, fn($a, $b) => filemtime($a) <=> filemtime($b) ?: strcmp($a, $b)); $t = '';
  foreach ($f as $x) if (preg_match('#/'.$pagina.'\.php\?token=([a-f0-9]{64})#', file_get_contents($x), $m)) $t = $m[1];
  return $t; }
$base = ['nombre'=>'Ana Prueba','email'=>'Ana@Correo.com','clave'=>'Clave1234','clave2'=>'Clave1234','acepto'=>'1'];

[$e,] = registrar_usuario(['clave'=>'corta','clave2'=>'corta'] + $base); t('rechaza contraseña corta', isset($e['clave']));
[$e,] = registrar_usuario(['clave'=>'sololetras','clave2'=>'sololetras'] + $base); t('exige letra y número', isset($e['clave']));
[$e,] = registrar_usuario(['email'=>'no-es-correo'] + $base); t('rechaza correo inválido', isset($e['email']));
[$e,] = registrar_usuario(['acepto'=>''] + $base); t('exige aceptar términos', isset($e['acepto']));
[$e,$id] = registrar_usuario($base); t('registro correcto', !$e && $id > 0);
t('correo guardado en minúsculas', usuario_por_email('ana@correo.com') !== null);
$hash = usuario_por_id($id)['clave_hash']; t('contraseña guardada con hash (no en claro)', $hash !== 'Clave1234' && password_verify('Clave1234', $hash));
[$e,] = registrar_usuario($base); t('rechaza correo duplicado', isset($e['email']));
t('registra correo de verificación', ultimo_token('verificar') !== '');
t('verificar correo con token', verificar_correo(ultimo_token('verificar')) && usuario_por_id($id)['email_verificado_en'] !== null);
t('token de verificación no se reutiliza', !verificar_correo(ultimo_token('verificar')));
t('login con clave incorrecta falla', iniciar_login('ana@correo.com', 'mala') !== null);
t("inyección SQL en el correo no rompe nada", iniciar_login("' OR '1'='1' --", 'x') !== null && usuario_por_email('ana@correo.com') !== null);
t('login correcto', iniciar_login('ANA@correo.com', 'Clave1234') === null && $_SESSION['uid'] === $id);
for ($i = 0; $i < 5; $i++) iniciar_login('ana@correo.com', 'mala'); 
t('bloquea tras 5 intentos fallidos', str_contains((string)iniciar_login('ana@correo.com', 'Clave1234'), 'Demasiados'));
db()->exec('DELETE FROM intentos_login'); $_SESSION = [];
// recuperación
solicitar_recuperacion('ana@correo.com'); $tok = ultimo_token('restablecer');
t('recuperación genera token', strlen($tok) === 64);
t('restablecer rechaza clave débil sin gastar el token', isset(restablecer_clave($tok, 'abc', 'abc')['clave']) && token_valido($tok, 'recuperar'));
t('restablecer con token válido', restablecer_clave($tok, 'Nueva5678', 'Nueva5678') === []);
t('token de recuperación no se reutiliza', isset(restablecer_clave($tok, 'Otra91011', 'Otra91011')['general']));
t('la clave vieja ya no sirve', iniciar_login('ana@correo.com', 'Clave1234') !== null);
t('la clave nueva sirve', iniciar_login('ana@correo.com', 'Nueva5678') === null);
// versión de sesión y suspensión
$u = usuario_por_id($id); $ver = (int)$_SESSION['ver']; cerrar_otras_sesiones($u);
t('cerrar otras sesiones sube la versión', (int)usuario_por_id($id)['version_sesion'] > $ver);
db()->exec("UPDATE usuarios SET estado='suspendido' WHERE id=$id"); $_SESSION = []; db()->exec('DELETE FROM intentos_login');
t('cuenta suspendida no puede entrar', str_contains((string)iniciar_login('ana@correo.com', 'Nueva5678'), 'suspendida'));
t('queda registro de auditoría', (int)db()->query('SELECT COUNT(*) FROM auditoria')->fetchColumn() > 5);
t('destino_seguro bloquea redirección externa', destino_seguro('//evil.com') === BASE.'/index.php' && destino_seguro('https://evil.com') === BASE.'/index.php' && destino_seguro('/reuns/public/x') === '/reuns/public/x');
echo "\n$ok correctas, $fallos fallidas\n"; exit($fallos ? 1 : 0);
