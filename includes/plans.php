<?php
/**
 * Planes editables desde el panel de administración.
 * config.php define los valores por defecto; la tabla plan_settings guarda
 * los cambios hechos por el administrador y tiene prioridad.
 */

/** Copia intacta de los planes de config.php (para "restaurar"). */
function default_plans(): array
{
    static $defaults = null;
    if ($defaults === null) {
        global $PLANS;
        $defaults = $PLANS;
    }
    return $defaults;
}

/** Aplica sobre $PLANS los valores guardados en la base de datos. */
function apply_plan_overrides(): void
{
    global $PLANS;
    default_plans(); // guarda la copia original antes de modificar
    try {
        $rows = db_all('SELECT * FROM plan_settings');
    } catch (PDOException $e) {
        return; // tabla aún no creada: se usan los valores de config.php
    }
    foreach ($rows as $r) {
        if (!isset($PLANS[$r['code']])) {
            continue;
        }
        $features = json_decode((string) $r['features'], true);
        $PLANS[$r['code']] = array_merge($PLANS[$r['code']], [
            'name'     => $r['name'],
            'tagline'  => (string) $r['tagline'],
            'price'    => (int) round((float) $r['price']),
            'seats'    => (int) $r['seats'],
            'months'   => (int) $r['months'],
            'featured' => (bool) $r['featured'],
            'features' => is_array($features) ? $features : $PLANS[$r['code']]['features'],
            'ai_monthly_quota' => (int) ($r['ai_monthly_quota'] ?? 0) ?: ($PLANS[$r['code']]['ai_monthly_quota'] ?? 0),
            'ai_member_quota'  => (int) ($r['ai_member_quota'] ?? 0) ?: ($PLANS[$r['code']]['ai_member_quota'] ?? 0),
            // Los minutos del tutor sí pueden ser 0: es como se apaga en un plan.
            'tutor_minutes'        => isset($r['tutor_minutes']) ? (int) $r['tutor_minutes'] : ($PLANS[$r['code']]['tutor_minutes'] ?? 0),
            'tutor_member_minutes' => isset($r['tutor_member_minutes']) ? (int) $r['tutor_member_minutes'] : ($PLANS[$r['code']]['tutor_member_minutes'] ?? 0),
            'payment_link' => (string) ($r['payment_link'] ?? '') !== ''
                ? (string) $r['payment_link']
                : ($PLANS[$r['code']]['payment_link'] ?? ''),
            'updated_at' => $r['updated_at'],
        ]);
    }
}

/**
 * Valida y guarda un plan. Devuelve la lista de errores (vacía si se guardó).
 */
function save_plan(string $code, array $input, int $adminId): array
{
    // Texto que no llegue en UTF-8 (p. ej. Windows-1252) se convierte en vez de romper el INSERT
    foreach (['name', 'tagline', 'features', 'payment_link'] as $k) {
        if (isset($input[$k]) && is_string($input[$k]) && !mb_check_encoding($input[$k], 'UTF-8')) {
            $input[$k] = mb_convert_encoding($input[$k], 'UTF-8', 'Windows-1252');
        }
    }
    $defaults = default_plans();
    if (!isset($defaults[$code])) {
        return ['Plan desconocido.'];
    }
    $errors = [];
    $name = trim((string) ($input['name'] ?? ''));
    $tagline = trim((string) ($input['tagline'] ?? ''));
    $price = (int) preg_replace('/\D/', '', (string) ($input['price'] ?? ''));
    $seats = (int) ($input['seats'] ?? 0);
    $months = (int) ($input['months'] ?? 0);
    $features = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($input['features'] ?? ''))), 'strlen'));
    $link = trim((string) ($input['payment_link'] ?? ''));
    $gens = (int) ($input['ai_monthly_quota'] ?? 0);
    $gensDoc = (int) ($input['ai_member_quota'] ?? 0);
    $min = (int) ($input['tutor_minutes'] ?? 0);
    $minDoc = (int) ($input['tutor_member_minutes'] ?? 0);

    if ($name === '' || mb_strlen($name) > 100)   $errors[] = 'El nombre es obligatorio (máx. 100 caracteres).';
    if (mb_strlen($tagline) > 200)                 $errors[] = 'La descripción corta admite máximo 200 caracteres.';
    if ($price < 1000)                             $errors[] = 'El precio mínimo es $1.000 COP.';
    if ($link === '') {
        $errors[] = 'El link de pago es obligatorio: sin él los clientes no pueden pagar este plan.';
    } elseif (!filter_var($link, FILTER_VALIDATE_URL) || !str_starts_with(strtolower($link), 'https://')) {
        $errors[] = 'El link de pago debe ser una dirección https válida (ej. https://mpago.li/xxxxxxx).';
    } elseif (mb_strlen($link) > 500) {
        $errors[] = 'El link de pago es demasiado largo.';
    }
    if ($price > 500000000)                        $errors[] = 'El precio supera el máximo permitido.';
    if ($seats < 1 || $seats > 100000)             $errors[] = 'Los usuarios deben estar entre 1 y 100.000.';
    if ($months < 1 || $months > 60)               $errors[] = 'La vigencia debe estar entre 1 y 60 meses.';
    if (!$features)                                $errors[] = 'Escribe al menos una característica.';
    if (count($features) > 20)                     $errors[] = 'Máximo 20 características.';
    if ($gens < 1 || $gens > 1000000)              $errors[] = 'Las generaciones al mes deben estar entre 1 y 1.000.000.';
    if ($gensDoc < 0 || $gensDoc > $gens)          $errors[] = 'El tope por docente no puede superar las generaciones de la licencia.';
    if ($min < 0 || $min > 100000)                 $errors[] = 'Los minutos de tutor deben estar entre 0 y 100.000.';
    if ($minDoc < 0 || $minDoc > max(1, $min))     $errors[] = 'Los minutos por docente no pueden superar los de la licencia.';
    if ($errors) {
        return $errors;
    }

    db_exec(
        'INSERT INTO plan_settings (code, name, tagline, price, seats, months, featured, features, payment_link,
                ai_monthly_quota, ai_member_quota, tutor_minutes, tutor_member_minutes, updated_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE name = VALUES(name), tagline = VALUES(tagline), price = VALUES(price),
             seats = VALUES(seats), months = VALUES(months), featured = VALUES(featured),
             features = VALUES(features), payment_link = VALUES(payment_link),
             ai_monthly_quota = VALUES(ai_monthly_quota), ai_member_quota = VALUES(ai_member_quota),
             tutor_minutes = VALUES(tutor_minutes), tutor_member_minutes = VALUES(tutor_member_minutes),
             updated_by = VALUES(updated_by), updated_at = NOW()',
        [$code, $name, $tagline, $price, $seats, $months, !empty($input['featured']) ? 1 : 0,
         json_encode(array_map(fn($f) => mb_substr($f, 0, 150), $features), JSON_UNESCAPED_UNICODE), $link,
         $gens, $gensDoc, $min, $minDoc, $adminId]
    );
    app_log('admin', 'Plan actualizado', ['plan' => $code, 'price' => $price, 'seats' => $seats, 'months' => $months,
        'link' => $link, 'gens' => $gens, 'tutor_min' => $min, 'admin' => $adminId]);
    return [];
}

function reset_plan(string $code, int $adminId): void
{
    db_exec('DELETE FROM plan_settings WHERE code = ?', [$code]);
    app_log('admin', 'Plan restaurado a valores por defecto', ['plan' => $code, 'admin' => $adminId]);
}
