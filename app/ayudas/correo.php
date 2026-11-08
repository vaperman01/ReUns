<?php
// MAIL_MODO=archivo: guarda el correo en storage/correos (solo pruebas, no se envía nada).
function enviar_correo(string $para, string $asunto, string $cuerpo): bool {
  if (env('MAIL_MODO', 'archivo') !== 'archivo') { error_log('[ReUns] SMTP aún no configurado (Etapa 8).'); return false; }
  $dir = APP.'/../storage/correos'; if (!is_dir($dir)) mkdir($dir, 0775, true);
  $f = $dir.'/'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.txt';
  return file_put_contents($f, "Para: $para\nAsunto: $asunto\n\n$cuerpo\n") !== false;
}
function url_app(string $ruta): string { return rtrim(env('APP_URL', 'http://localhost'), '/').BASE.$ruta; }
