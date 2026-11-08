<?php
function auditar(?int $uid, string $accion, ?string $detalle = null): void {
  try { db()->prepare('INSERT INTO auditoria (usuario_id, accion, detalle, ip) VALUES (?,?,?,?)')
    ->execute([$uid, $accion, $detalle, $_SERVER['REMOTE_ADDR'] ?? 'cli']); }
  catch (Throwable $t) { error_log('[ReUns] auditoría: '.$t->getMessage()); }
}
