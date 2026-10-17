<?php
/**
 * Cliente de IA (Google Gemini) con salida JSON estructurada.
 *
 * La clave viaja en la cabecera x-goog-api-key, nunca en la URL ni en los registros.
 * Toda respuesta se pide como JSON con un esquema obligatorio: así el resultado
 * llega siempre con la forma que espera cada herramienta.
 */

const AI_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/';

/** Códigos HTTP que vale la pena reintentar (saturación o fallo temporal). */
const AI_RETRY_HTTP = [429, 500, 502, 503, 504];

function ai_configured(): bool
{
    return AI_API_KEY !== '';
}

/** Devuelve el modelo del nivel pedido: 'fast' para el día a día, 'smart' para documentos largos. */
function ai_model(string $tier = 'fast'): string
{
    $model = $tier === 'smart' ? AI_MODEL_SMART : AI_MODEL_FAST;
    return preg_match('/^[a-z0-9.\-]{3,60}$/', $model) ? $model : 'gemini-2.5-flash';
}

/** Costo de una generación en millonésimas de dólar, según la tabla de precios de config.php. */
function ai_cost_micro(string $model, int $promptTokens, int $outputTokens): int
{
    global $AI_PRICES;
    $base = preg_replace('/-\d{3,}$/', '', $model);           // gemini-2.5-flash-001 -> gemini-2.5-flash
    $price = $AI_PRICES[$model] ?? $AI_PRICES[$base] ?? null;
    if ($price === null) {
        return 0;
    }
    $usd = ($promptTokens / 1000000) * $price['in'] + ($outputTokens / 1000000) * $price['out'];
    return (int) round($usd * 1000000);
}

function ai_cost_usd(int $micro): string
{
    return '$' . number_format($micro / 1000000, 4, ',', '.');
}

/**
 * Genera contenido con el modelo y devuelve JSON validado contra $schema.
 *
 * Nunca lanza excepciones: devuelve ok=false con un mensaje apto para el usuario.
 *
 * @param array $opts  tier, temperature, max_tokens, thinking, model
 * @return array{ok:bool, data?:array, model:string, prompt_tokens:int, output_tokens:int,
 *               cost_micro:int, seconds:float, error?:string, billed?:bool}
 */
function ai_json(string $system, string $user, array $schema, array $opts = []): array
{
    $model = (string) ($opts['model'] ?? ai_model((string) ($opts['tier'] ?? 'fast')));
    $empty = ['model' => $model, 'prompt_tokens' => 0, 'output_tokens' => 0, 'cost_micro' => 0, 'seconds' => 0.0];

    if (!ai_configured()) {
        return $empty + ['ok' => false, 'error' => 'Las herramientas de IA no están configuradas. Avisa al administrador.'];
    }

    $generation = [
        'responseMimeType' => 'application/json',
        'responseSchema'   => $schema,
        'temperature'      => (float) ($opts['temperature'] ?? 0.6),
        'maxOutputTokens'  => (int) ($opts['max_tokens'] ?? 8192),
    ];
    // En los modelos 2.5 el razonamiento consume del mismo tope de salida:
    // se acota para que el JSON no quede truncado a mitad de camino.
    if (str_starts_with($model, 'gemini-2.5')) {
        $generation['thinkingConfig'] = ['thinkingBudget' => (int) ($opts['thinking'] ?? 1024)];
    }

    $payload = [
        'systemInstruction' => ['parts' => [['text' => $system]]],
        'contents'          => [['role' => 'user', 'parts' => [['text' => $user]]]],
        'generationConfig'  => $generation,
    ];

    // La llamada puede tardar: se libera la sesión para no bloquear otras
    // peticiones del mismo docente mientras el modelo responde.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    $started = microtime(true);
    $attempt = 0;
    $lastError = 'No se pudo conectar con el servicio de IA.';

    while ($attempt < 3) {
        $attempt++;
        $res = ai_post($model, $payload);

        if ($res['status'] === 200) {
            $parsed = ai_parse($res['body'], $model);
            $parsed['seconds'] = round(microtime(true) - $started, 1);
            if ($parsed['ok'] || !$parsed['retryable']) {
                unset($parsed['retryable']);
                app_log('ai', $parsed['ok'] ? 'Generación completada' : 'Respuesta inutilizable', [
                    'modelo' => $parsed['model'], 'segundos' => $parsed['seconds'],
                    'entrada' => $parsed['prompt_tokens'], 'salida' => $parsed['output_tokens'],
                    'intento' => $attempt, 'error' => $parsed['error'] ?? null,
                ]);
                return $parsed;
            }
            $lastError = $parsed['error'];
        } elseif (!in_array($res['status'], AI_RETRY_HTTP, true) && $res['status'] !== 0) {
            // 4xx: el problema es nuestro, reintentar no cambia nada.
            app_log('ai', 'La API respondió con error', ['http' => $res['status'], 'modelo' => $model, 'detalle' => $res['detail']]);
            return $empty + ['ok' => false, 'error' => 'El servicio de IA rechazó la solicitud (HTTP ' . $res['status'] . ').'];
        } else {
            $lastError = $res['status'] === 0
                ? 'No se pudo conectar con el servicio de IA.'
                : 'El servicio de IA está saturado (HTTP ' . $res['status'] . ').';
            app_log('ai', 'Reintentando', ['http' => $res['status'], 'intento' => $attempt, 'modelo' => $model]);
        }

        if ($attempt < 3) {
            sleep($attempt * 3);   // 3 s y luego 6 s
        }
    }

    return $empty + ['ok' => false, 'error' => $lastError . ' Inténtalo de nuevo en unos minutos.'];
}

/**
 * Una sola petición HTTP a la API.
 * @return array{status:int, body:string, detail:string}
 */
function ai_post(string $model, array $payload): array
{
    $ch = curl_init(AI_ENDPOINT . rawurlencode($model) . ':generateContent');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-goog-api-key: ' . AI_API_KEY],
        CURLOPT_POSTFIELDS     => (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT        => AI_TIMEOUT,
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return ['status' => 0, 'body' => '', 'detail' => $error];
    }
    $detail = '';
    if ($status >= 400) {
        $json = json_decode((string) $body, true);
        $detail = mb_substr((string) ($json['error']['status'] ?? $json['error']['message'] ?? ''), 0, 200);
    }
    return ['status' => $status, 'body' => (string) $body, 'detail' => $detail];
}

/**
 * Interpreta la respuesta del modelo.
 * @return array{ok:bool, retryable:bool, data?:array, model:string, prompt_tokens:int, output_tokens:int, cost_micro:int, error?:string, billed?:bool}
 */
function ai_parse(string $body, string $model): array
{
    $json = json_decode($body, true);
    if (!is_array($json)) {
        return ['ok' => false, 'retryable' => true, 'model' => $model, 'prompt_tokens' => 0, 'output_tokens' => 0,
                'cost_micro' => 0, 'error' => 'La respuesta de la IA no se pudo leer.'];
    }

    $candidate = $json['candidates'][0] ?? null;
    $finish = (string) ($candidate['finishReason'] ?? '');
    $text = '';
    foreach ((array) ($candidate['content']['parts'] ?? []) as $part) {
        // Las partes marcadas como "thought" son razonamiento interno, no contenido.
        if (is_array($part) && empty($part['thought']) && isset($part['text']) && is_string($part['text'])) {
            $text .= $part['text'];
        }
    }

    $usage = (array) ($json['usageMetadata'] ?? []);
    $in = (int) ($usage['promptTokenCount'] ?? 0);
    $out = (int) ($usage['candidatesTokenCount'] ?? 0) + (int) ($usage['thoughtsTokenCount'] ?? 0);
    $modelUsed = (string) ($json['modelVersion'] ?? $model);
    // Una respuesta 200 sin contenido utilizable se cobra igual: se registra el consumo.
    $base = ['model' => $modelUsed, 'prompt_tokens' => $in, 'output_tokens' => $out,
             'cost_micro' => ai_cost_micro($modelUsed, $in, $out), 'billed' => true];

    if ($text === '') {
        $blocked = (string) ($json['promptFeedback']['blockReason'] ?? '');
        return $base + ['ok' => false, 'retryable' => $blocked === '', 'error' => $blocked !== ''
            ? 'El contenido fue bloqueado por las políticas de seguridad del modelo. Reformula la solicitud.'
            : 'La IA no devolvió contenido' . ($finish !== '' ? ' (' . $finish . ')' : '') . '.'];
    }

    $clean = trim($text);
    if (str_starts_with($clean, '```')) {
        $clean = (string) preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $clean);
    }
    $data = json_decode($clean, true);
    if (!is_array($data)) {
        return $base + ['ok' => false, 'retryable' => $finish !== 'MAX_TOKENS', 'error' => $finish === 'MAX_TOKENS'
            ? 'La respuesta quedó incompleta porque el documento era muy largo. Intenta acotar la solicitud.'
            : 'La respuesta de la IA no tiene el formato esperado.'];
    }

    return $base + ['ok' => true, 'retryable' => false, 'data' => $data];
}

/**
 * Texto que el docente escribió y que el modelo NO debe interpretar como órdenes.
 * Se neutralizan los delimitadores para que nadie pueda cerrar el bloque e inyectar instrucciones.
 */
function ai_quote(string $text): string
{
    $text = str_replace(['<<<', '>>>'], ['‹‹‹', '›››'], $text);
    return trim(mb_substr($text, 0, 6000));
}
