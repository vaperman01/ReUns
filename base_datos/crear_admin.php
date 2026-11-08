<?php
// Crea (o promueve) un administrador. Uso: php base_datos/crear_admin.php
if (PHP_SAPI !== 'cli') exit('Solo desde la terminal.');
require __DIR__.'/../app/config/config.php'; require APP.'/config/db.php';
require APP.'/ayudas/auditoria.php'; require APP.'/modelos/usuario.php'; require APP.'/controladores/auth.php';
function pedir(string $t): string { echo $t.': '; return trim((string)fgets(STDIN)); }
$nombre = pedir('Nombre'); $email = strtolower(pedir('Correo')); $clave = pedir('Contraseña (se verá al escribir)');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($nombre) < 2 || validar_clave_nueva($clave)) exit("Datos no válidos (contraseña: 8+ caracteres con letra y número).\n");
$u = usuario_por_email($email);
if ($u) { db()->prepare("UPDATE usuarios SET rol='admin' WHERE id=?")->execute([$u['id']]); $id = (int)$u['id']; }
else { $id = crear_usuario($nombre, $email, password_hash($clave, PASSWORD_DEFAULT)); db()->prepare("UPDATE usuarios SET rol='admin', email_verificado_en=NOW() WHERE id=?")->execute([$id]); }
auditar($id, 'admin_creado', 'por terminal');
echo "Listo: $email es administrador.\n";
