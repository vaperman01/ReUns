<?php
// Punto de consulta del chat (JSON). GET = mensajes nuevos; POST = enviar un mensaje.
require __DIR__ . '/../../app/ayudas/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function json_salida(int $codigo, array $datos): never {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$u = usuario_actual();
if (!$u) json_salida(401, ['error' => 'Tu sesión expiró. Inicia sesión de nuevo.']);
$uid = (int)$u['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        json_salida(419, ['error' => 'Sesión expirada. Recarga la página.']);
    }
    [$id, $err] = enviar_mensaje((int)($_POST['c'] ?? 0), $uid, (string)($_POST['texto'] ?? ''));
    if ($id) json_salida(200, ['ok' => true, 'id' => $id]);
    json_salida(str_contains((string)$err, 'No encontramos') ? 404 : 422, ['error' => $err]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mensajes = mensajes_de((int)($_GET['c'] ?? 0), $uid, max(0, (int)($_GET['desde'] ?? 0)));
    if ($mensajes === null) json_salida(404, ['error' => 'No encontramos esa conversación.']);
    json_salida(200, ['mensajes' => $mensajes]);
}

json_salida(405, ['error' => 'Método no permitido.']);
