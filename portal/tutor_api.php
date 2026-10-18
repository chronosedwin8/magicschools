<?php
/**
 * Latido y cierre de la conversación con el tutor (JSON).
 *
 * El latido no dice "sigo conectado": dice cuántos segundos llevan sin que la
 * persona interactúe. Con ese dato el servidor puede cerrar la conversación
 * aunque el navegador se quede sin red justo después.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => 'auth', 'closed' => true]);
    exit;
}

// El cierre al abandonar la página llega por sendBeacon y no puede llevar cabecera,
// así que el token va en el cuerpo y se compara aquí.
$token = (string) ($_POST['csrf'] ?? $_GET['csrf'] ?? '');
if (!hash_equals(csrf_token(), $token)) {
    http_response_code(419);
    echo json_encode(['status' => 'csrf', 'closed' => false]);
    exit;
}

$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$sesion = tutor_session($id);
if (!$sesion || (int) $sesion['user_id'] !== (int) $user['id']) {
    http_response_code(404);
    echo json_encode(['status' => 'missing', 'closed' => true]);
    exit;
}

$accion = (string) ($_POST['action'] ?? $_GET['action'] ?? 'beat');
$motivos = ['inactividad', 'cerro_pestana', 'docente', 'tope_tiempo'];

switch ($accion) {
    case 'end':
        $motivo = (string) ($_POST['reason'] ?? 'docente');
        $estado = tutor_state(tutor_end($id, in_array($motivo, $motivos, true) ? $motivo : 'docente'));
        break;
    case 'state':
        $estado = tutor_state($sesion);
        break;
    default:
        $estado = tutor_beat($sesion, (int) ($_POST['idle'] ?? 0));
}

echo json_encode($estado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
