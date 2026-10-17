<?php
/**
 * Ejecución de una herramienta.
 *
 * El servidor del sitio corta las peticiones a los 60 segundos y una generación
 * puede tardar más, así que la respuesta se envía primero y el trabajo continúa
 * en segundo plano. Si el servidor no lo permite, se genera en la misma petición.
 */

/** Contexto que se inyecta en los prompts: perfil del docente e institución. */
function ai_build_context(array $user, array $license): array
{
    $perfil = db_one('SELECT * FROM teacher_profiles WHERE user_id = ?', [$user['id']]) ?: [];
    return [
        'institution' => (string) ($license['institution'] ?? $user['institution'] ?? ''),
        'grades'      => (string) ($perfil['grades'] ?? ''),
        'areas'       => (string) ($perfil['areas'] ?? ''),
        'approach'    => (string) ($perfil['approach'] ?? ''),
    ];
}

/** Llama al modelo y guarda el resultado. No lanza excepciones. */
function ai_run_generation(int $id, array $tool, array $input, array $ctx): void
{
    try {
        $system = ($tool['system'])($ctx);
        $user = ($tool['user'])($input, $ctx);
        $res = ai_json($system, $user, $tool['schema'], [
            'tier'       => $tool['tier'],
            'max_tokens' => (int) ($tool['max_tokens'] ?? 8192),
            'thinking'   => (int) ($tool['thinking'] ?? 1024),
            'temperature' => (float) ($tool['temperature'] ?? 0.6),
        ]);

        if (!$res['ok']) {
            ai_fail_generation($id, $res['error'], $res);
            return;
        }
        $markdown = ai_render_markdown($tool, $res['data']);
        if (trim($markdown) === '') {
            ai_fail_generation($id, 'La respuesta llegó vacía. Inténtalo de nuevo.', $res);
            return;
        }
        ai_finish_generation($id, $res, $markdown);
    } catch (Throwable $e) {
        app_log('ai', 'Error inesperado al generar', ['id' => $id, 'error' => $e->getMessage()]);
        ai_fail_generation($id, 'Ocurrió un error inesperado al generar el documento.');
    }
}

/**
 * Envía la redirección al navegador y ejecuta la generación después.
 * Nunca retorna.
 */
function ai_dispatch(int $id, array $tool, array $input, array $ctx, string $redirectTo): never
{
    $url = preg_match('#^https?://#', $redirectTo) ? $redirectTo : url($redirectTo);

    // Se cierra la sesión antes de responder: el trabajo posterior no la necesita
    // y así el docente puede seguir navegando mientras el modelo escribe.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    header('Location: ' . $url);
    header('Content-Length: 0');
    header('Connection: close');

    if (function_exists('fastcgi_finish_request')) {
        // PHP-FPM: la respuesta ya viajó; aquí hay tiempo de sobra.
        @set_time_limit(AI_MAX_PENDING_SECONDS);
        ignore_user_abort(true);
        fastcgi_finish_request();
        ai_run_generation($id, $tool, $input, $ctx);
        exit;
    }

    // Sin FPM (por ejemplo, Apache en local): se genera antes de redirigir.
    @set_time_limit(AI_MAX_PENDING_SECONDS);
    ignore_user_abort(true);
    ai_run_generation($id, $tool, $input, $ctx);
    exit;
}
