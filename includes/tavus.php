<?php
/**
 * Cliente de Tavus (tutor con avatar).
 *
 * Reglas de costo que no se negocian, aprendidas en producción:
 *  - Tavus cobra desde que se CREA la conversación, aunque nadie entre.
 *  - La duración se redondea a múltiplos de 6 s con un mínimo de 30 s.
 *  - POST /conversations NUNCA se reintenta: un reintento crearía (y cobraría)
 *    una segunda conversación.
 *  - Siempre se envía max_call_duration y los tiempos de ausencia.
 *  - conversation_name lleva el id de nuestra sesión para poder cazar
 *    conversaciones huérfanas si se pierde la respuesta.
 */

const TAVUS_ENDPOINT = 'https://tavusapi.com/v2';
const TAVUS_ROUNDING_SECONDS = 6;
const TAVUS_MIN_BILLED_SECONDS = 30;

function tavus_configured(): bool
{
    return TAVUS_API_KEY !== '' && TAVUS_REPLICA_ID !== '';
}

/** El tutor atiende solo si está configurado y el interruptor del panel está arriba. */
function tavus_enabled(): bool
{
    return tavus_configured() && setting_on('tutor_enabled', true);
}

function tavus_cost_per_min_micro(): int
{
    return (int) round(TAVUS_COST_PER_MIN_USD * 1000000);
}

/** Segundos facturables: hacia arriba a múltiplos de 6 s, mínimo 30 s. */
function tavus_billed_seconds(int $seconds): int
{
    $rounded = (int) (ceil(max(0, $seconds) / TAVUS_ROUNDING_SECONDS) * TAVUS_ROUNDING_SECONDS);
    return max(TAVUS_MIN_BILLED_SECONDS, $rounded);
}

function tavus_cost_micro(int $billedSeconds): int
{
    return (int) round($billedSeconds / 60 * tavus_cost_per_min_micro());
}

function tavus_minutes(int $seconds): string
{
    return number_format($seconds / 60, 1, ',', '.');
}

/**
 * Llamada a la API.
 *
 * @param int $retries reintentos (0 para todo lo que cueste dinero)
 * @return array{ok:bool, status:int, data:array, error:string, definite:bool}
 *         definite=true significa que Tavus respondió y con certeza no creó nada.
 */
function tavus_request(string $method, string $path, ?array $body = null, int $retries = 0, int $timeout = 20): array
{
    if (TAVUS_API_KEY === '') {
        return ['ok' => false, 'status' => 0, 'data' => [], 'error' => 'El tutor con avatar no está configurado.', 'definite' => true];
    }
    $payload = $body === null ? null : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    $last = ['ok' => false, 'status' => 0, 'data' => [], 'error' => 'Tavus no respondió.', 'definite' => false];

    for ($attempt = 0; $attempt <= $retries; $attempt++) {
        if ($attempt > 0) {
            sleep($attempt);
        }
        $ch = curl_init(TAVUS_ENDPOINT . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => ['x-api-key: ' . TAVUS_API_KEY, 'Content-Type: application/json'],
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $netError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            // Sin respuesta: NO sabemos si la operación ocurrió. No se reintenta lo que cuesta.
            $last = ['ok' => false, 'status' => 0, 'data' => [], 'error' => 'Tavus no respondió: ' . $netError, 'definite' => false];
            continue;
        }
        $data = json_decode((string) $raw, true);
        $data = is_array($data) ? $data : [];
        if ($status >= 200 && $status < 300) {
            return ['ok' => true, 'status' => $status, 'data' => $data, 'error' => '', 'definite' => true];
        }
        $msg = (string) ($data['message'] ?? $data['error'] ?? substr((string) $raw, 0, 200));
        $last = [
            'ok' => false, 'status' => $status, 'data' => $data,
            'error' => 'Tavus ' . $status . ': ' . $msg,
            'definite' => $status >= 400 && $status < 500,   // 4xx: rechazada, nada se creó
        ];
        if ($status !== 429 && $status < 500) {
            break;
        }
    }
    app_log('tavus', 'fallo ' . $method . ' ' . $path, ['status' => $last['status'], 'error' => $last['error']]);
    return $last;
}

/** Crea la conversación. Sin reintentos, por diseño. */
function tavus_create_conversation(array $body): array
{
    return tavus_request('POST', '/conversations', $body, 0, 25);
}

/** Cierra la conversación. Un 4xx significa que ya estaba cerrada: objetivo cumplido. */
function tavus_end_conversation(string $conversationId): bool
{
    $r = tavus_request('POST', '/conversations/' . rawurlencode($conversationId) . '/end', null, 3, 15);
    return $r['ok'] || ($r['status'] >= 400 && $r['status'] < 500);
}

function tavus_get_conversation(string $conversationId): ?array
{
    $r = tavus_request('GET', '/conversations/' . rawurlencode($conversationId), null, 2, 15);
    return $r['ok'] ? $r['data'] : null;
}

/** Busca una conversación activa por su nombre: así se recuperan las huérfanas. */
function tavus_find_active(string $name): ?array
{
    $r = tavus_request('GET', '/conversations?status=active&limit=100', null, 2, 20);
    if (!$r['ok']) {
        return null;
    }
    foreach (($r['data']['data'] ?? $r['data']) as $conv) {
        if (is_array($conv) && ($conv['conversation_name'] ?? '') === $name) {
            return $conv;
        }
    }
    return null;
}
