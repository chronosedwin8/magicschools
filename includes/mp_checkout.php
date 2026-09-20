<?php
/**
 * Checkout API de Mercado Pago: pantalla de cobro propia.
 * Las tarjetas se tokenizan en el navegador con Secure Fields (MercadoPago.js),
 * por lo que los datos de la tarjeta nunca llegan a este servidor.
 */

/**
 * Medios de pago activos de la cuenta (caché de 6 horas en storage/).
 */
function mp_payment_methods(): array
{
    $cache = __DIR__ . '/../storage/mp_payment_methods.json';
    if (is_file($cache) && filemtime($cache) > time() - 21600) {
        $data = json_decode((string) file_get_contents($cache), true);
        if (is_array($data) && $data) {
            return $data;
        }
    }
    $res = mp_request('GET', '/v1/payment_methods');
    if ($res['status'] !== 200 || !is_array($res['body'])) {
        return [];
    }
    $active = array_values(array_filter($res['body'], fn($m) => ($m['status'] ?? '') === 'active'));
    @file_put_contents($cache, json_encode($active));
    return $active;
}

function mp_payment_method(string $id): ?array
{
    foreach (mp_payment_methods() as $m) {
        if ($m['id'] === $id) {
            return $m;
        }
    }
    return null;
}

/** ¿El medio de pago está activo y admite este monto? */
function mp_method_allows(string $id, float $amount): bool
{
    $m = mp_payment_method($id);
    return $m !== null
        && $amount >= (float) ($m['min_allowed_amount'] ?? 0)
        && $amount <= (float) ($m['max_allowed_amount'] ?? PHP_FLOAT_MAX);
}

/** Bancos disponibles para PSE, ordenados por nombre. */
function mp_pse_banks(): array
{
    $banks = mp_payment_method('pse')['financial_institutions'] ?? [];
    usort($banks, fn($a, $b) => strcasecmp($a['description'], $b['description']));
    return $banks;
}

function mp_client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    return $ip === '::1' ? '127.0.0.1' : $ip;
}

function mp_create_payment(array $payload, string $idempotencyKey, ?string $deviceId = null): array
{
    $headers = ['X-Idempotency-Key: ' . $idempotencyKey];
    if ($deviceId) {
        $headers[] = 'X-meli-session-id: ' . preg_replace('/[^\w\-:.]/', '', $deviceId);
    }
    return mp_request('POST', '/v1/payments', $payload, $headers);
}

function mp_statement_descriptor(): string
{
    return substr(preg_replace('/[^A-Z0-9 ]/', '', strtoupper(iconv('UTF-8', 'ASCII//TRANSLIT', MP_STATEMENT_DESCRIPTOR))), 0, 22);
}

/**
 * Campos comunes de todo pago. El monto sale SIEMPRE de la orden en la BD.
 */
function mp_base_payment(array $order, array $plan): array
{
    $payload = [
        'transaction_amount'   => (float) $order['amount'],
        'description'          => BRAND_NAME . ' - ' . $plan['name'],
        'external_reference'   => (string) $order['reference'],
        'statement_descriptor' => mp_statement_descriptor(),
        'metadata'             => ['order_id' => (int) $order['id'], 'plan' => $plan['code']],
        'additional_info'      => [
            'ip_address' => mp_client_ip(),
            'items'      => [[
                'id'          => $plan['code'],
                'title'       => BRAND_NAME . ' - ' . $plan['name'],
                'description' => $plan['seats'] . ' usuarios por ' . $plan['months'] . ' meses',
                'category_id' => 'services',
                'quantity'    => 1,
                'unit_price'  => (float) $order['amount'],
            ]],
        ],
    ];
    if (!is_local_url(base_url())) {
        $payload['notification_url'] = url('pago/webhook.php?source_news=webhooks');
    }
    return $payload;
}

/** Mensaje para el cliente según el resultado del pago. */
function mp_status_detail_message(string $status, string $detail): string
{
    $map = [
        'accredited'                           => '¡Pago aprobado!',
        'pending_contingency'                  => 'Estamos procesando tu pago. En menos de 2 días hábiles te confirmaremos el resultado.',
        'pending_review_manual'                => 'Estamos revisando tu pago. En menos de 2 días hábiles te confirmaremos el resultado.',
        'pending_waiting_payment'              => 'Tu pago está pendiente. Complétalo para activar la licencia.',
        'pending_waiting_transfer'             => 'Completa el pago en la página de tu banco para activar la licencia.',
        'pending_challenge'                    => 'Tu banco necesita que confirmes la compra.',
        'cc_rejected_bad_filled_card_number'   => 'Revisa el número de la tarjeta.',
        'cc_rejected_bad_filled_date'          => 'Revisa la fecha de vencimiento.',
        'cc_rejected_bad_filled_other'         => 'Revisa los datos de la tarjeta.',
        'cc_rejected_bad_filled_security_code' => 'Revisa el código de seguridad de la tarjeta.',
        'cc_rejected_blacklist'                => 'No pudimos procesar tu pago. Usa otra tarjeta u otro medio de pago.',
        'cc_rejected_call_for_authorize'       => 'Debes autorizar este pago con tu banco. Llama al número que aparece al respaldo de tu tarjeta.',
        'cc_rejected_card_disabled'            => 'Tu tarjeta no está activa. Llama a tu banco o usa otra tarjeta.',
        'cc_rejected_card_error'               => 'No pudimos procesar tu pago. Inténtalo de nuevo.',
        'cc_rejected_duplicated_payment'       => 'Ya hiciste un pago por este valor. Si necesitas pagar de nuevo, usa otra tarjeta u otro medio.',
        'cc_rejected_high_risk'                => 'Tu pago fue rechazado por seguridad. Usa otro medio de pago, por ejemplo PSE.',
        'cc_rejected_insufficient_amount'      => 'La tarjeta no tiene cupo suficiente.',
        'cc_rejected_invalid_installments'     => 'La tarjeta no admite ese número de cuotas.',
        'cc_rejected_max_attempts'             => 'Llegaste al límite de intentos. Usa otra tarjeta u otro medio de pago.',
        'cc_rejected_3ds_challenge'            => 'No se completó la verificación de seguridad de tu banco.',
        'cc_rejected_3ds_mandatory'            => 'Tu banco exige verificación adicional. Inténtalo de nuevo o usa PSE.',
        'cc_rejected_card_type_not_allowed'    => 'Este tipo de tarjeta no está permitido. Usa otra tarjeta.',
        'cc_amount_rate_limit_exceeded'        => 'Superaste el límite de monto de tu tarjeta. Usa otro medio de pago.',
        'rejected_by_bank'                     => 'Tu banco rechazó el pago.',
        'rejected_insufficient_data'           => 'Faltan datos para procesar el pago.',
    ];
    return $map[$detail] ?? match ($status) {
        'approved'              => '¡Pago aprobado!',
        'pending', 'in_process' => 'Tu pago está en proceso.',
        'cancelled'             => 'El pago fue cancelado.',
        default                 => 'Tu pago fue rechazado. Revisa los datos o usa otro medio de pago.',
    };
}

/** Departamentos de Colombia (requeridos por PSE en la dirección del pagador). */
function co_departments(): array
{
    return ['Amazonas', 'Antioquia', 'Arauca', 'Atlántico', 'Bogotá D.C.', 'Bolívar', 'Boyacá', 'Caldas', 'Caquetá',
        'Casanare', 'Cauca', 'Cesar', 'Chocó', 'Córdoba', 'Cundinamarca', 'Guainía', 'Guaviare', 'Huila', 'La Guajira',
        'Magdalena', 'Meta', 'Nariño', 'Norte de Santander', 'Putumayo', 'Quindío', 'Risaralda', 'San Andrés y Providencia',
        'Santander', 'Sucre', 'Tolima', 'Valle del Cauca', 'Vaupés', 'Vichada'];
}
