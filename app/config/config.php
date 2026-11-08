<?php
// Si tu carpeta no se llama "reuns", cambia BASE.
const BASE = '/reuns/public';
const APP  = __DIR__ . '/..';
function env(string $k, ?string $d = null): ?string {
  static $f = null;
  if ($f === null) { $f = []; $r = APP . '/../.env';
    if (is_file($r)) foreach (file($r, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
      $l = trim($l); if ($l === '' || $l[0] === '#' || !str_contains($l, '=')) continue;
      [$a, $b] = explode('=', $l, 2); $f[trim($a)] = trim($b); } }
  $v = getenv($k); if ($v !== false) return $v;
  return $f[$k] ?? $d;
}
function e(?string $t): string { return htmlspecialchars($t ?? '', ENT_QUOTES, 'UTF-8'); }
function soles(float $n): string { return 'S/ ' . number_format($n, 2); }
