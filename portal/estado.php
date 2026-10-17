<?php
/**
 * Estado de una generación (JSON). Lo consulta la página mientras el modelo escribe.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => 'auth']);
    exit;
}

ai_expire_stale();
$gen = ai_generation((int) ($_GET['id'] ?? 0), $user);
if (!$gen) {
    http_response_code(404);
    echo json_encode(['status' => 'missing']);
    exit;
}

echo json_encode([
    'status'   => $gen['status'],
    'redirect' => $gen['status'] !== 'pending' ? url('portal/herramienta.php?gen=' . $gen['id']) : null,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
