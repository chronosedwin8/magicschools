<?php
/**
 * Integración con Mercado Pago (Checkout Pro) usando la API REST.
 * Documentación: https://www.mercadopago.com.co/developers/es/docs/checkout-pro
 */

const MP_API = 'https://api.mercadopago.com';

function mp_enabled(): bool
{
    return MP_ACCESS_TOKEN !== '';
}

function mp_demo(): bool
{
    return !mp_enabled() && DEMO_MODE;
}

/**
 * Llamada genérica a la API de Mercado Pago.
 * @return array{status:int, body:array|null, raw:string}
 */
function mp_request(string $method, string $path, ?array $payload = null, array $extraHeaders = []): array
{
    $ch = curl_init(MP_API . $path);
    $headers = array_merge([
        'Authorization: Bearer ' . MP_ACCESS_TOKEN,
        'Content-Type: application/json',
        'Accept: application/json',
    ], $extraHeaders);

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    // XAMPP en Windows suele no traer bundle de CA: usa el incluido si existe.
    $caBundle = __DIR__ . '/../storage/cacert.pem';
    if (is_file($caBundle)) {
        curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
    }
    if ($payload !== null) {
        // INVALID_UTF8_SUBSTITUTE evita que un carácter mal codificado deje el cuerpo vacío
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION);
        if ($json === false) {
            app_log('mercadopago', 'No se pudo codificar el JSON', ['path' => $path, 'error' => json_last_error_msg()]);
            curl_close($ch);
            return ['status' => 0, 'body' => null, 'raw' => 'json_encode'];
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    }

    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        app_log('mercadopago', 'Error cURL', ['path' => $path, 'error' => $err]);
        return ['status' => 0, 'body' => null, 'raw' => $err];
    }
    $body = json_decode($raw, true);
    if ($status >= 400) {
        app_log('mercadopago', 'Respuesta de error', ['path' => $path, 'status' => $status, 'body' => $body]);
    }
    return ['status' => $status, 'body' => is_array($body) ? $body : null, 'raw' => $raw];
}

/**
 * Crea la preferencia de pago para una orden y retorna la URL de pago.
 */
function mp_create_preference(array $order, array $user, array $plan): array
{
    $base = base_url();
    $back = [
        'success' => url('pago/retorno.php?estado=exito'),
        'failure' => url('pago/retorno.php?estado=fallo'),
        'pending' => url('pago/retorno.php?estado=pendiente'),
    ];

    $nameParts = preg_split('/\s+/', trim($user['name']), 2);

    $payload = [
        'items' => [[
            'id'          => $plan['code'],
            'title'       => BRAND_NAME . ' - ' . $plan['name'],
            'description' => $plan['seats'] . ' usuarios por ' . $plan['months'] . ' meses',
            'category_id' => 'services',
            'quantity'    => 1,
            'currency_id' => CURRENCY,
            'unit_price'  => (float) $order['amount'],
        ]],
        'payer' => [
            'name'    => $nameParts[0] ?? '',
            'surname' => $nameParts[1] ?? '',
            'email'   => $user['email'],
        ],
        'external_reference'   => (string) $order['reference'],
        'statement_descriptor' => substr(preg_replace('/[^A-Z0-9 ]/', '', strtoupper(iconv('UTF-8', 'ASCII//TRANSLIT', MP_STATEMENT_DESCRIPTOR))), 0, 22),
        'back_urls'            => $back,
        'metadata'             => ['order_id' => (int) $order['id'], 'plan' => $plan['code']],
        'expires'              => false,
    ];

    // Mercado Pago no acepta auto_return ni notification_url con URLs locales.
    if (!is_local_url($base)) {
        $payload['auto_return'] = 'approved';
        $payload['notification_url'] = url('pago/webhook.php?source_news=webhooks');
    }

    $res = mp_request('POST', '/checkout/preferences', $payload, [
        'X-Idempotency-Key: pref-' . $order['reference'],
    ]);

    if ($res['status'] !== 201 && $res['status'] !== 200) {
        $msg = $res['body']['message'] ?? 'No fue posible conectar con Mercado Pago.';
        throw new RuntimeException($msg);
    }

    $b = $res['body'];
    return [
        'id'       => $b['id'],
        'init_url' => MP_SANDBOX ? ($b['sandbox_init_point'] ?? $b['init_point']) : $b['init_point'],
    ];
}

function mp_get_payment(string $paymentId): ?array
{
    if (!ctype_digit($paymentId)) {
        return null;
    }
    $res = mp_request('GET', '/v1/payments/' . $paymentId);
    return $res['status'] === 200 ? $res['body'] : null;
}

function mp_get_merchant_order(string $id): ?array
{
    if (!ctype_digit($id)) {
        return null;
    }
    $res = mp_request('GET', '/merchant_orders/' . $id);
    return $res['status'] === 200 ? $res['body'] : null;
}

/**
 * Valida la firma x-signature de las notificaciones Webhook.
 * Si no hay secreto configurado se omite (se confía en la consulta a la API).
 */
function mp_valid_signature(?string $dataId): bool
{
    if (MP_WEBHOOK_SECRET === '') {
        return true;
    }
    $sig = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    $reqId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
    $ts = $v1 = null;
    foreach (explode(',', $sig) as $part) {
        [$k, $v] = array_map('trim', explode('=', $part, 2) + [1 => '']);
        if ($k === 'ts') $ts = $v;
        if ($k === 'v1') $v1 = $v;
    }
    if (!$ts || !$v1) {
        return false;
    }
    $manifest = '';
    if ($dataId !== null && $dataId !== '') {
        $manifest .= 'id:' . (ctype_alnum($dataId) ? strtolower($dataId) : $dataId) . ';';
    }
    if ($reqId !== '') {
        $manifest .= 'request-id:' . $reqId . ';';
    }
    $manifest .= 'ts:' . $ts . ';';
    return hash_equals(hash_hmac('sha256', $manifest, MP_WEBHOOK_SECRET), $v1);
}

/**
 * Traduce el estado de un pago de Mercado Pago a español.
 */
function mp_status_label(string $status): string
{
    return [
        'approved'     => 'Aprobado',
        'pending'      => 'Pendiente',
        'in_process'   => 'En revisión',
        'authorized'   => 'Autorizado',
        'in_mediation' => 'En mediación',
        'rejected'     => 'Rechazado',
        'cancelled'    => 'Cancelado',
        'refunded'     => 'Reembolsado',
        'charged_back' => 'Contracargo',
    ][$status] ?? ucfirst($status);
}
