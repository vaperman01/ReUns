<?php
// Contadores para los indicadores del menú (JSON). Solo lectura.
require __DIR__ . '/../../app/ayudas/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$u = usuario_actual();
if (!$u) {
    http_response_code(401);
    echo json_encode(['error' => 'Sin sesión']);
    exit;
}

echo json_encode([
    'notificaciones' => contar_notificaciones_no_leidas((int)$u['id']),
    'mensajes' => contar_mensajes_no_leidos((int)$u['id']),
    'operaciones' => contar_pendientes_recibidas((int)$u['id']),
]);
