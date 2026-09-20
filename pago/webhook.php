<?php
/**
 * Notificaciones (Webhooks / IPN) de Mercado Pago.
 * Configura en el panel de Mercado Pago la URL: {BASE_URL}/pago/webhook.php
 * Evento: "Pagos".
 */
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
$type = $_GET['type'] ?? $_GET['topic'] ?? $body['type'] ?? $body['topic'] ?? '';
$dataId = (string) ($_GET['data_id'] ?? $_GET['data.id'] ?? $_GET['id'] ?? $body['data']['id'] ?? '');

app_log('webhook', 'Notificación recibida', ['type' => $type, 'id' => $dataId]);

if (!mp_enabled()) {
    http_response_code(200);
    echo json_encode(['ok' => true, 'ignored' => 'mp_disabled']);
    exit;
}

if (!mp_valid_signature($dataId)) {
    app_log('webhook', 'Firma inválida', ['id' => $dataId]);
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

try {
    $paymentIds = [];
    if ($type === 'payment' && $dataId !== '') {
        $paymentIds[] = $dataId;
    } elseif ($type === 'merchant_order' && $dataId !== '') {
        $mo = mp_get_merchant_order($dataId);
        foreach ($mo['payments'] ?? [] as $p) {
            $paymentIds[] = (string) $p['id'];
        }
    }

    foreach ($paymentIds as $pid) {
        $payment = mp_get_payment($pid);
        if ($payment) {
            $order = apply_payment($payment);
            app_log('webhook', 'Pago procesado', ['payment' => $pid, 'status' => $payment['status'] ?? '', 'order' => $order['reference'] ?? null]);
        }
    }
    http_response_code(200);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    app_log('webhook', 'Error', ['error' => $e->getMessage()]);
    // 500 hace que Mercado Pago reintente la notificación
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
