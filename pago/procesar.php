<?php
/**
 * Crea el pago en Mercado Pago (Checkout API) y responde JSON a pago.js.
 * El monto se toma de la orden en la base de datos, nunca del navegador.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function respond(array $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $message, int $code = 422): never
{
    respond(['ok' => false, 'message' => $message], $code);
}

if (!is_post()) {
    fail('Método no permitido.', 405);
}
$user = current_user();
if (!$user) {
    fail('Tu sesión expiró. Inicia sesión de nuevo.', 401);
}
if (!hash_equals(csrf_token(), (string) ($_POST['_csrf'] ?? ''))) {
    fail('La sesión expiró. Recarga la página.', 419);
}
if (!mp_enabled()) {
    fail('Los pagos no están disponibles en este momento.', 503);
}

$order = order_by_reference((string) ($_POST['ref'] ?? ''));
if (!$order || (int) $order['user_id'] !== (int) $user['id']) {
    fail('Pedido no encontrado.', 404);
}
if ($order['status'] === 'approved') {
    $lic = db_one('SELECT id FROM licenses WHERE order_id = ?', [$order['id']]);
    respond(['ok' => true, 'status' => 'approved', 'redirect' => url($lic ? 'portal/licencia.php?id=' . $lic['id'] : 'portal/')]);
}

// Límite de intentos por pedido (10 cada 30 minutos)
$key = '_pay_attempts_' . $order['id'];
$_SESSION[$key] = array_filter($_SESSION[$key] ?? [], fn($t) => $t > time() - 1800);
if (count($_SESSION[$key]) >= 10) {
    fail('Hiciste demasiados intentos. Espera unos minutos o escríbenos a ' . SUPPORT_EMAIL . '.', 429);
}
$_SESSION[$key][] = time();

$plan = plan($order['plan_code']);
$method = (string) ($_POST['method'] ?? '');
$email = mb_strtolower(trim((string) ($_POST['email'] ?? $user['email'])));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Escribe un correo válido.');
}
$docType = in_array($_POST['doc_type'] ?? '', ['CC', 'CE', 'NIT', 'Otro'], true) ? $_POST['doc_type'] : 'CC';
$docNumber = preg_replace('/[^0-9A-Za-z]/', '', (string) ($_POST['doc_number'] ?? ''));
if (strlen($docNumber) < 5) {
    fail('Escribe un número de documento válido.');
}

$nameParts = preg_split('/\s+/', trim($user['name']), 2);
$firstName = trim((string) ($_POST['first_name'] ?? '')) ?: ($nameParts[0] ?? '');
$lastName = trim((string) ($_POST['last_name'] ?? '')) ?: ($nameParts[1] ?? '');

$payload = mp_base_payment($order, $plan);
$payload['payer'] = [
    'email'          => $email,
    'first_name'     => mb_substr($firstName, 0, 60),
    'last_name'      => mb_substr($lastName, 0, 60),
    'identification' => ['type' => $docType, 'number' => $docNumber],
];
$payload['additional_info']['payer'] = [
    'first_name' => mb_substr($firstName, 0, 60),
    'last_name'  => mb_substr($lastName, 0, 60),
];

switch ($method) {
    case 'card':
        $token = (string) ($_POST['token'] ?? '');
        $pmId = (string) ($_POST['payment_method_id'] ?? '');
        if (!preg_match('/^[a-f0-9]{16,64}$/i', $token) || !preg_match('/^[a-z_]{2,20}$/', $pmId)) {
            fail('No pudimos leer los datos de la tarjeta. Revísalos e inténtalo de nuevo.');
        }
        $payload['token'] = $token;
        $payload['payment_method_id'] = $pmId;
        $payload['installments'] = max(1, min(48, (int) ($_POST['installments'] ?? 1)));
        if (ctype_digit((string) ($_POST['issuer_id'] ?? ''))) {
            $payload['issuer_id'] = (int) $_POST['issuer_id'];
        }
        $holder = preg_split('/\s+/', trim((string) ($_POST['cardholder'] ?? '')), 2);
        if (!empty($holder[0])) {
            $payload['payer']['first_name'] = mb_substr($holder[0], 0, 60);
            $payload['payer']['last_name'] = mb_substr($holder[1] ?? '', 0, 60);
        }
        $payload['three_d_secure_mode'] = 'optional';
        break;

    case 'pse':
        if (!mp_method_allows('pse', (float) $order['amount'])) {
            fail('PSE no está disponible para este valor.');
        }
        $bank = (string) ($_POST['bank'] ?? '');
        $validBanks = array_column(mp_pse_banks(), 'id');
        if (!in_array($bank, array_map('strval', $validBanks), true)) {
            fail('Selecciona tu banco.');
        }
        $phone = preg_replace('/\D/', '', (string) ($_POST['phone'] ?? ''));
        $fields = ['street' => 'la dirección', 'street_number' => 'el número de la dirección', 'neighborhood' => 'el barrio', 'city' => 'la ciudad', 'department' => 'el departamento'];
        foreach ($fields as $f => $label) {
            if (trim((string) ($_POST[$f] ?? '')) === '') {
                fail('Escribe ' . $label . '.');
            }
        }
        if (strlen($phone) < 7) {
            fail('Escribe un teléfono válido.');
        }
        $payload['payment_method_id'] = 'pse';
        $payload['payer']['entity_type'] = ($_POST['entity_type'] ?? '') === 'individual' ? 'individual' : 'association';
        $payload['payer']['address'] = [
            'zip_code'      => preg_replace('/\D/', '', (string) ($_POST['zip'] ?? '')) ?: '000000',
            'street_name'   => mb_substr(trim((string) $_POST['street']), 0, 80),
            'street_number' => mb_substr(trim((string) $_POST['street_number']), 0, 20),
            'neighborhood'  => mb_substr(trim((string) $_POST['neighborhood']), 0, 60),
            'city'          => mb_substr(trim((string) $_POST['city']), 0, 60),
            'federal_unit'  => mb_substr(trim((string) $_POST['department']), 0, 60),
        ];
        $payload['payer']['phone'] = ['area_code' => '57', 'number' => substr($phone, -10)];
        $payload['transaction_details'] = ['financial_institution' => $bank];
        $payload['callback_url'] = url('pago/retorno.php?external_reference=' . urlencode($order['reference']));
        break;

    case 'efecty':
        if (!mp_method_allows('efecty', (float) $order['amount'])) {
            fail('Efecty no está disponible para este valor. Usa tarjeta o PSE.');
        }
        $payload['payment_method_id'] = 'efecty';
        $payload['date_of_expiration'] = (new DateTime('+3 days'))->format('Y-m-d\TH:i:s.vP');
        break;

    default:
        fail('Selecciona un medio de pago.');
}

$res = mp_create_payment($payload, 'pay-' . $order['reference'] . '-' . bin2hex(random_bytes(6)), (string) ($_POST['device_id'] ?? ''));
$payment = $res['body'];

if (!in_array($res['status'], [200, 201], true) || empty($payment['id'])) {
    app_log('mercadopago', 'Pago no creado', ['order' => $order['reference'], 'method' => $method, 'status' => $res['status'], 'body' => $payment]);
    $cause = $payment['cause'][0]['description'] ?? $payment['message'] ?? '';
    fail('No pudimos procesar el pago. ' . (APP_DEBUG && $cause ? '(' . $cause . ')' : 'Revisa los datos o intenta con otro medio de pago.'));
}

$order = apply_payment($payment) ?? $order;
$status = (string) ($payment['status'] ?? '');
$detail = (string) ($payment['status_detail'] ?? '');
$message = mp_status_detail_message($status, $detail);
$externalUrl = $payment['transaction_details']['external_resource_url'] ?? null;

if ($externalUrl && $status === 'pending') {
    db_exec('UPDATE orders SET payment_url = ?, payment_method = ?, updated_at = NOW() WHERE id = ?', [$externalUrl, $payment['payment_method_id'] ?? $method, $order['id']]);
}

// 1) Aprobado: al portal
if ($status === 'approved') {
    $lic = db_one('SELECT id FROM licenses WHERE order_id = ?', [$order['id']]);
    flash('success', '¡Pago aprobado! Tu ' . $order['plan_name'] . ' ya está activa. Agrega ahora a los usuarios de tu institución.');
    respond(['ok' => true, 'status' => 'approved', 'message' => $message,
        'redirect' => url($lic ? 'portal/licencia.php?id=' . $lic['id'] : 'portal/')]);
}

// 2) Tarjeta con verificación 3-D Secure
if ($detail === 'pending_challenge' && !empty($payment['three_ds_info']['external_resource_url'])) {
    respond(['ok' => true, 'status' => 'challenge', 'message' => $message, 'three_ds' => [
        'url'  => $payment['three_ds_info']['external_resource_url'],
        'creq' => $payment['three_ds_info']['creq'] ?? '',
    ]]);
}

// 3) PSE: redirigir al banco (solo si el pago quedó pendiente)
if ($method === 'pse' && $externalUrl && in_array($status, ['pending', 'in_process'], true)) {
    respond(['ok' => true, 'status' => 'pending', 'message' => $message, 'redirect' => $externalUrl]);
}

// 4) Efecty o pago en revisión: página de resultado
if (in_array($status, ['pending', 'in_process', 'authorized'], true)) {
    respond(['ok' => true, 'status' => 'pending', 'message' => $message,
        'redirect' => url('pago/resultado.php?ref=' . urlencode($order['reference']))]);
}

// 5) Rechazado
respond(['ok' => false, 'status' => $status, 'message' => $message]);
