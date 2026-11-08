<?php
function iniciar_sesion(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  ini_set('session.use_strict_mode', '1'); ini_set('session.use_only_cookies', '1');
  session_name('reuns_sid');
  session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Lax']);
  session_start();
  if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff'); header('X-Frame-Options: DENY'); header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:; script-src 'self'; form-action 'self'; frame-ancestors 'none'");
  }
  if (isset($_SESSION['ultimo']) && time() - $_SESSION['ultimo'] > 7200) { $_SESSION = []; session_regenerate_id(true); } // 2 h sin actividad
  $_SESSION['ultimo'] = time();
}
function usuario_actual(): ?array {
  static $cache = false;
  if ($cache !== false) return $cache;
  $cache = null;
  if (!empty($_SESSION['uid'])) {
    $u = usuario_por_id((int)$_SESSION['uid']);
    if ($u && $u['estado'] === 'activo' && (int)$u['version_sesion'] === (int)($_SESSION['ver'] ?? 0)) $cache = $u;
    else { $_SESSION = []; }
  }
  return $cache;
}
function requerir_login(): array {
  $u = usuario_actual();
  if (!$u) { flash('info', 'Inicia sesión para continuar.'); redirigir(BASE.'/paginas/login.php?volver='.urlencode($_SERVER['REQUEST_URI'] ?? '')); }
  return $u;
}
function requerir_rol(string ...$roles): array {
  $u = requerir_login();
  if (!in_array($u['rol'], $roles, true)) pagina_error(403, 'Sin permiso', 'No tienes permiso para ver esta página.');
  return $u;
}
