<?php
/**
 * Ayuda de IA para un campo del formulario (JSON).
 * La llama assets/js/tools.js desde los botones que acompañan a cada campo largo.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Tu sesión expiró. Vuelve a entrar.']);
    exit;
}
if (!is_post() || !hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'error' => 'Recarga la página e inténtalo de nuevo.']);
    exit;
}

$license = current_license($user);
if (!$license || !license_is_usable($license)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Necesitas una licencia activa para usar la ayuda de IA.']);
    exit;
}

$tool = ai_tool((string) ($_POST['h'] ?? ''));
if (!$tool) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Esa herramienta no existe.']);
    exit;
}

// El campo tiene que ser uno de los de esta herramienta y admitir texto libre.
$campo = null;
foreach ((array) ($tool['fields'] ?? []) as $f) {
    if ($f['name'] === (string) ($_POST['campo'] ?? '') && in_array($f['type'], ['textarea', 'text'], true)) {
        $campo = $f;
        break;
    }
}
if (!$campo) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Ese campo no admite ayuda de IA.']);
    exit;
}

$modo = (string) ($_POST['modo'] ?? 'mejorar');
if (!in_array($modo, ['mejorar', 'crear'], true)) {
    $modo = 'mejorar';
}

// La llamada tarda unos segundos: se libera la sesión para no bloquear la página.
$datos = $_POST;
session_write_close();

$r = ai_assist_field($tool, $campo, $modo, (string) ($_POST['texto'] ?? ''), $datos, $user, $license);
if (!$r['ok']) {
    http_response_code(422);
}
echo json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
