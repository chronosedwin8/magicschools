<?php
/**
 * Cuotas de uso y medición de consumo de las herramientas de IA.
 *
 * El límite vive en el plan y se congela en la licencia al venderla, para que
 * un cambio de precios no altere licencias ya entregadas.
 */

function ai_period(): string
{
    return date('Ym');
}

/**
 * Cuotas efectivas de una licencia: primero las congeladas, si no las del plan.
 * @return array{license:int, member:int}
 */
function ai_quota_for(array $license): array
{
    $plan = plan((string) $license['plan_code']);
    return [
        'license' => (int) ($license['ai_monthly_quota'] ?: ($plan['ai_monthly_quota'] ?? 0)),
        'member'  => (int) ($license['ai_member_quota'] ?: ($plan['ai_member_quota'] ?? 0)),
    ];
}

/** Consumo del periodo. $userId = 0 devuelve el total de la licencia. */
function ai_usage(int $licenseId, int $userId = 0, ?string $period = null): array
{
    $row = db_one(
        'SELECT generations, prompt_tokens, output_tokens, cost_micro_usd FROM ai_usage_month
          WHERE license_id = ? AND user_id = ? AND period = ?',
        [$licenseId, $userId, $period ?? ai_period()]
    );
    return $row ?: ['generations' => 0, 'prompt_tokens' => 0, 'output_tokens' => 0, 'cost_micro_usd' => 0];
}

/**
 * ¿Puede generar? Devuelve el mensaje de error o null, igual que add_license_member().
 */
function ai_quota_check(array $license, array $user): ?string
{
    if (!license_is_usable($license)) {
        return 'La licencia de tu institución no está activa.';
    }

    // El administrador del sitio usa todas las herramientas sin tope,
    // pero su consumo sí se mide y aparece en el panel de costos.
    $sinTope = is_admin($user);

    $quota = ai_quota_for($license);
    $period = ai_period();
    if ($sinTope) {
        $quota = ['license' => 0, 'member' => 0];
    }

    if ($quota['license'] > 0) {
        $usada = (int) ai_usage((int) $license['id'], 0, $period)['generations'];
        if ($usada >= $quota['license']) {
            return 'Tu institución agotó las ' . $quota['license'] . ' generaciones de este mes. El cupo se renueva el día 1.';
        }
    }
    if ($quota['member'] > 0) {
        $mia = (int) ai_usage((int) $license['id'], (int) $user['id'], $period)['generations'];
        if ($mia >= $quota['member']) {
            return 'Alcanzaste tu límite personal de ' . $quota['member'] . ' generaciones este mes. Pídele más cupo a quien administra la licencia.';
        }
    }

    // Freno sencillo contra ráfagas: 12 generaciones por minuto por persona
    $recientes = (int) db_one(
        'SELECT COUNT(*) c FROM ai_generations WHERE user_id = ? AND created_at > NOW() - INTERVAL 1 MINUTE',
        [$user['id']]
    )['c'];
    if ($recientes >= 12) {
        return 'Vas muy rápido. Espera un momento antes de generar de nuevo.';
    }

    return null;
}

/** Crea la fila en estado pendiente y devuelve su id. */
function ai_start_generation(array $license, array $user, array $tool, array $input, string $title): int
{
    db_exec(
        'INSERT INTO ai_generations (license_id, user_id, author_name, author_email, tool_slug, tool_name, status, title, input_json)
         VALUES (?, ?, ?, ?, ?, ?, "pending", ?, ?)',
        [
            $license['id'], $user['id'], mb_substr($user['name'], 0, 120), mb_substr($user['email'], 0, 190),
            $tool['slug'], mb_substr($tool['name'], 0, 100), mb_substr($title, 0, 190),
            json_encode($input, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
        ]
    );
    return (int) db()->lastInsertId();
}

/** Guarda el resultado y suma el consumo del mes. */
function ai_finish_generation(int $id, array $result, string $text, string $format = 'markdown'): void
{
    db_exec(
        'UPDATE ai_generations SET status = "done", output_text = ?, output_json = ?, output_format = ?, model = ?,
                prompt_tokens = ?, output_tokens = ?, cost_micro_usd = ?, finished_at = NOW()
          WHERE id = ? AND status = "pending"',
        [$text, json_encode($result['data'] ?? null, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
         $format, $result['model'], $result['prompt_tokens'], $result['output_tokens'], $result['cost_micro'], $id]
    );
    $gen = db_one('SELECT license_id, user_id FROM ai_generations WHERE id = ?', [$id]);
    if ($gen) {
        ai_add_usage((int) $gen['license_id'], (int) $gen['user_id'], $result);
    }
}

/**
 * Marca la generación como fallida. No consume cuota: el consumo solo se suma
 * cuando hay resultado, salvo que el modelo ya haya facturado tokens.
 */
function ai_fail_generation(int $id, string $error, array $result = []): void
{
    db_exec(
        'UPDATE ai_generations SET status = "error", error = ?, model = ?, prompt_tokens = ?, output_tokens = ?,
                cost_micro_usd = ?, finished_at = NOW() WHERE id = ? AND status = "pending"',
        [mb_substr($error, 0, 255), $result['model'] ?? null, $result['prompt_tokens'] ?? 0,
         $result['output_tokens'] ?? 0, $result['cost_micro'] ?? 0, $id]
    );
    // Si el proveedor cobró los tokens, el costo se registra aunque no haya resultado.
    if (!empty($result['billed']) && !empty($result['cost_micro'])) {
        $gen = db_one('SELECT license_id, user_id FROM ai_generations WHERE id = ?', [$id]);
        if ($gen) {
            ai_add_usage((int) $gen['license_id'], (int) $gen['user_id'], $result, false);
        }
    }
}

/** Suma al contador del mes, tanto el de la licencia como el del docente. */
function ai_add_usage(int $licenseId, int $userId, array $result, bool $countGeneration = true): void
{
    $period = ai_period();
    $n = $countGeneration ? 1 : 0;
    foreach ([0, $userId] as $uid) {
        db_exec(
            'INSERT INTO ai_usage_month (license_id, user_id, period, generations, prompt_tokens, output_tokens, cost_micro_usd)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE generations = generations + VALUES(generations),
                 prompt_tokens = prompt_tokens + VALUES(prompt_tokens),
                 output_tokens = output_tokens + VALUES(output_tokens),
                 cost_micro_usd = cost_micro_usd + VALUES(cost_micro_usd)',
            [$licenseId, $uid, $period, $n, $result['prompt_tokens'] ?? 0, $result['output_tokens'] ?? 0, $result['cost_micro'] ?? 0]
        );
    }
}

/** Una generación que quedó colgada se marca como error (la llama la página de estado). */
function ai_expire_stale(): void
{
    db_exec(
        'UPDATE ai_generations SET status = "error", error = "La generación tardó demasiado y se canceló.", finished_at = NOW()
          WHERE status = "pending" AND created_at < NOW() - INTERVAL ? SECOND',
        [AI_MAX_PENDING_SECONDS]
    );
}

/** Generación del usuario (o de un administrador, que puede ver cualquiera). */
function ai_generation(int $id, array $user): ?array
{
    if (is_admin($user)) {
        return db_one('SELECT * FROM ai_generations WHERE id = ?', [$id]);
    }
    return db_one('SELECT * FROM ai_generations WHERE id = ? AND user_id = ?', [$id, $user['id']]);
}
