<?php
require_once __DIR__.'/../config/config.php';
require_once APP.'/config/db.php';
require_once APP.'/ayudas/sesion.php';
require_once APP.'/ayudas/seguridad.php';
require_once APP.'/ayudas/formularios.php';
require_once APP.'/ayudas/auditoria.php';
require_once APP.'/ayudas/correo.php';
require_once APP.'/modelos/usuario.php';
require_once APP.'/controladores/auth.php';
require_once APP.'/ayudas/imagenes.php';
require_once APP.'/ayudas/paginacion.php';
require_once APP.'/modelos/producto.php';
require_once APP.'/controladores/productos.php';
require_once APP.'/modelos/operacion.php';
require_once APP.'/modelos/notificacion.php';
require_once APP.'/modelos/favorito.php';
require_once APP.'/modelos/chat.php';
require_once APP.'/modelos/calificacion.php';
set_exception_handler(function (Throwable $t) {
  error_log('[ReUns] '.$t->getMessage().' en '.$t->getFile().':'.$t->getLine());
  if (!headers_sent()) http_response_code(500);
  echo '<!DOCTYPE html><meta charset="utf-8"><title>Error</title><p style="font:16px sans-serif;padding:2rem">Ocurrió un problema. Inténtalo de nuevo en unos minutos.</p>';
});
iniciar_sesion();
foreach ($_POST as $k => $v) if (is_array($v) && $k !== 'quitar') $_POST[$k] = '';
foreach ($_GET as $k => $v) if (is_array($v)) $_GET[$k] = '';
