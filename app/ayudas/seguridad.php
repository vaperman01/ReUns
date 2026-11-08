<?php
function redirigir(string $url): never { header('Location: '.$url); exit; }
function destino_seguro(?string $v): string { // solo rutas locales, evita redirecciones abiertas
  return ($v && preg_match('#^/(?!/)[^\\\\\r\n]*$#', $v)) ? $v : BASE.'/index.php';
}
function ip_cliente(): string { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_campo(): string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function verificar_csrf(): void {
  $t = $_POST['csrf'] ?? '';
  if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) pagina_error(419, 'Sesión expirada', 'Recarga la página e inténtalo de nuevo.');
}
function flash(string $tipo, string $msg): void { $_SESSION['flash'][] = [$tipo, $msg]; }
function mostrar_flash(): void {
  foreach ($_SESSION['flash'] ?? [] as [$t, $m]) echo '<p class="aviso aviso--'.e($t).'" role="status">'.e($m).'</p>';
  unset($_SESSION['flash']);
}
function pagina_error(int $codigo, string $titulo, string $msg): never {
  http_response_code($codigo);
  require APP.'/vistas/cabecera.php';
  echo '<div class="contenedor"><div class="vacio" style="margin:36px 0"><h1 style="font-size:1.5rem">'.e($titulo).'</h1><p>'.e($msg).'</p><a class="btn btn--primario" href="'.BASE.'/index.php">Ir al inicio</a></div></div>';
  require APP.'/vistas/pie.php'; exit;
}
