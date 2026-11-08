<?php
function paginacion(int $pag, int $paginas, array $params): string {
  if ($paginas <= 1) return ''; unset($params['pag']);
  $link = fn(int $n, string $t, bool $act = false) => '<a'.($act ? ' aria-current="page" class="activa"' : '').' href="?'.e(http_build_query($params + ['pag' => $n])).'">'.$t.'</a>';
  $h = '<nav class="paginacion" aria-label="Páginas">';
  if ($pag > 1) $h .= $link($pag - 1, '‹ Anterior');
  for ($i = max(1, $pag - 2); $i <= min($paginas, $pag + 2); $i++) $h .= $link($i, (string)$i, $i === $pag);
  if ($pag < $paginas) $h .= $link($pag + 1, 'Siguiente ›');
  return $h.'</nav>';
}
