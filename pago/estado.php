<?php
/**
 * Consulta el estado actual del pago de una orden (JSON).
 * Lo usa pago.js durante la verificación 3-D Secure y la página de resultado.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$user = current_user();
$order = $user ? order_by_reference((string) ($_GET['ref'] ?? '')) : null;
if (!$order || (int) $order['user_id'] !== (int) $user['id']) {
    http_response_code(404);
    echo json_encode(['ok' => false]);
    exit;
}

$detail = (string) ($order['status_detail'] ?? '');
if ($order['status'] !== 'approved' && mp_enabled()) {
    $payment = $order['mp_payment_id'] ? mp_get_payment((string) $order['mp_payment_id']) : null;
    if ($payment && ($payment['external_reference'] ?? '') === $order['reference']) {
        $order = apply_payment($payment) ?? $order;
        $detail = (string) ($payment['status_detail'] ?? $detail);
    } elseif (!$order['mp_payment_id']) {
        $order = sync_order_with_mp($order) ?? $order;
        $detail = (string) ($order['status_detail'] ?? $detail);
    }
}

$out = ['ok' => true, 'status' => $order['status'], 'detail' => $detail,
        'message' => mp_status_detail_message($order['status'], $detail)];
if ($order['status'] === 'approved') {
    $lic = db_one('SELECT id FROM licenses WHERE order_id = ?', [$order['id']]);
    $out['redirect'] = url($lic ? 'portal/licencia.php?id=' . $lic['id'] : 'portal/');
    if (($_GET['flash'] ?? '') === '1') {
        flash('success', '¡Pago aprobado! Tu ' . $order['plan_name'] . ' ya está activa. Agrega ahora a los usuarios de tu institución.');
    }
}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
