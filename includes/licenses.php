<?php
/**
 * Órdenes, licencias y miembros.
 */

function order_by_reference(string $reference): ?array
{
    return db_one('SELECT * FROM orders WHERE reference = ?', [$reference]);
}

function create_order(int $userId, array $plan): array
{
    $reference = 'AM-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    db_exec(
        'INSERT INTO orders (reference, user_id, plan_code, plan_name, seats, amount, currency, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, "pending")',
        [$reference, $userId, $plan['code'], $plan['name'], $plan['seats'], $plan['price'], CURRENCY]
    );
    return order_by_reference($reference);
}

function generate_license_key(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $groups = [];
    for ($g = 0; $g < 4; $g++) {
        $chunk = '';
        for ($i = 0; $i < 4; $i++) {
            $chunk .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $groups[] = $chunk;
    }
    return implode('-', $groups);
}

/**
 * Aplica el resultado de un pago a una orden. Idempotente:
 * si la orden ya fue aprobada no se crea otra licencia.
 *
 * @param array $payment  Datos del pago (formato API de Mercado Pago o simulador)
 * @return array|null     Orden actualizada
 */
function apply_payment(array $payment): ?array
{
    $reference = (string) ($payment['external_reference'] ?? '');
    if ($reference === '') {
        return null;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $order = db_one('SELECT * FROM orders WHERE reference = ? FOR UPDATE', [$reference]);
        if (!$order) {
            $pdo->rollBack();
            app_log('payments', 'Orden no encontrada', ['reference' => $reference]);
            return null;
        }

        $status = (string) ($payment['status'] ?? 'pending');
        $paymentId = (string) ($payment['id'] ?? '');

        db_exec(
            'INSERT INTO payment_events (order_id, mp_payment_id, status, status_detail, amount, payload)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $order['id'], $paymentId, $status, $payment['status_detail'] ?? null,
                $payment['transaction_amount'] ?? null,
                json_encode($payment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );

        if ($order['status'] === 'approved') {
            // Ya procesada; solo registrar reembolsos / contracargos
            if (in_array($status, ['refunded', 'charged_back'], true)) {
                db_exec('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?', [$status, $order['id']]);
                db_exec('UPDATE licenses SET status = "suspended" WHERE order_id = ?', [$order['id']]);
            }
            $pdo->commit();
            return order_by_reference($reference);
        }

        if ($status === 'approved') {
            $paid = (float) ($payment['transaction_amount'] ?? 0);
            $currency = (string) ($payment['currency_id'] ?? CURRENCY);
            if ($paid + 0.5 < (float) $order['amount'] || $currency !== $order['currency']) {
                db_exec(
                    'UPDATE orders SET status = "review", mp_payment_id = ?, status_detail = ?, updated_at = NOW() WHERE id = ?',
                    [$paymentId, 'Monto o moneda no coincide', $order['id']]
                );
                $pdo->commit();
                app_log('payments', 'Monto no coincide', ['order' => $reference, 'paid' => $paid, 'currency' => $currency]);
                return order_by_reference($reference);
            }

            db_exec(
                'UPDATE orders SET status = "approved", mp_payment_id = ?, payment_method = ?, status_detail = ?,
                        paid_at = NOW(), updated_at = NOW() WHERE id = ?',
                [$paymentId, $payment['payment_method_id'] ?? null, $payment['status_detail'] ?? null, $order['id']]
            );

            $plan = plan($order['plan_code']);
            $months = $plan['months'] ?? 12;
            db_exec(
                'INSERT INTO licenses (order_id, user_id, plan_code, plan_name, license_key, seats, starts_at, expires_at, status)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? MONTH), "active")',
                [$order['id'], $order['user_id'], $order['plan_code'], $order['plan_name'], generate_license_key(), $order['seats'], $months]
            );
        } else {
            $mapped = match ($status) {
                'rejected'               => 'rejected',
                'cancelled'              => 'cancelled',
                'in_process', 'pending',
                'authorized'             => 'pending',
                default                  => $order['status'],
            };
            db_exec(
                'UPDATE orders SET status = ?, mp_payment_id = ?, status_detail = ?, updated_at = NOW() WHERE id = ?',
                [$mapped, $paymentId ?: $order['mp_payment_id'], $payment['status_detail'] ?? null, $order['id']]
            );
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        app_log('payments', 'Error aplicando pago', ['error' => $e->getMessage()]);
        throw $e;
    }

    return order_by_reference($reference);
}

function user_licenses(int $userId): array
{
    return db_all(
        'SELECT l.*, o.reference, o.amount, o.currency, o.paid_at, o.mp_payment_id, o.payment_method,
                (SELECT COUNT(*) FROM license_members m WHERE m.license_id = l.id) AS used_seats
           FROM licenses l
           JOIN orders o ON o.id = l.order_id
          WHERE l.user_id = ?
          ORDER BY l.created_at DESC',
        [$userId]
    );
}

function user_license(int $userId, int $licenseId): ?array
{
    return db_one(
        'SELECT l.*, o.reference, o.amount, o.currency, o.paid_at, o.mp_payment_id, o.payment_method,
                (SELECT COUNT(*) FROM license_members m WHERE m.license_id = l.id) AS used_seats
           FROM licenses l
           JOIN orders o ON o.id = l.order_id
          WHERE l.user_id = ? AND l.id = ?',
        [$userId, $licenseId]
    );
}

/** Licencia por id sin filtrar por dueño (uso exclusivo de administradores). */
function license_by_id(int $licenseId): ?array
{
    return db_one(
        'SELECT l.*, o.reference, o.amount, o.currency, o.paid_at, o.mp_payment_id, o.payment_method,
                u.name AS owner_name, u.email AS owner_email, u.institution AS owner_institution,
                (SELECT COUNT(*) FROM license_members m WHERE m.license_id = l.id) AS used_seats
           FROM licenses l
           JOIN orders o ON o.id = l.order_id
           JOIN users u ON u.id = l.user_id
          WHERE l.id = ?',
        [$licenseId]
    );
}

/**
 * Aprueba manualmente una orden (transferencia, pago por fuera, cortesía).
 * Reutiliza apply_payment para crear la licencia de forma idempotente.
 */
function approve_order_manually(array $order, string $note = 'manual'): ?array
{
    return apply_payment([
        'id'                 => 'MANUAL-' . date('ymdHis'),
        'status'             => 'approved',
        'status_detail'      => mb_substr('Aprobado por administrador: ' . $note, 0, 120),
        'external_reference' => $order['reference'],
        'transaction_amount' => (float) $order['amount'],
        'currency_id'        => $order['currency'],
        'payment_method_id'  => 'manual',
    ]);
}

/**
 * Consulta en Mercado Pago los pagos de una orden y aplica el más reciente.
 */
function sync_order_with_mp(array $order): ?array
{
    $res = mp_request('GET', '/v1/payments/search?sort=date_created&criteria=desc&external_reference=' . urlencode($order['reference']));
    $results = $res['body']['results'] ?? [];
    if (!$results) {
        return null;
    }
    // Prioriza un pago aprobado si existe
    $payment = $results[0];
    foreach ($results as $p) {
        if (($p['status'] ?? '') === 'approved') {
            $payment = $p;
            break;
        }
    }
    return apply_payment($payment);
}

function license_members(int $licenseId): array
{
    return db_all('SELECT * FROM license_members WHERE license_id = ? ORDER BY created_at DESC, id DESC', [$licenseId]);
}

function license_is_usable(array $license): bool
{
    return $license['status'] === 'active' && strtotime($license['expires_at']) > time();
}

function license_status_label(array $license): array
{
    if ($license['status'] === 'suspended') {
        return ['Suspendida', 'danger'];
    }
    if (strtotime($license['expires_at']) <= time()) {
        return ['Vencida', 'muted'];
    }
    $days = (int) floor((strtotime($license['expires_at']) - time()) / 86400);
    if ($days <= 30) {
        return ['Vence en ' . $days . ' días', 'warning'];
    }
    return ['Activa', 'success'];
}

/**
 * Agrega un miembro a una licencia validando cupos y duplicados.
 * @return string|null  Mensaje de error o null si se agregó
 */
function add_license_member(array $license, string $name, string $email, string $role, ?string $area = null): ?string
{
    global $MEMBER_ROLES;
    $name = trim($name);
    $email = mb_strtolower(trim($email));
    $area = $area !== null ? trim($area) : null;

    if ($name === '' || mb_strlen($name) > 120) {
        return 'El nombre es obligatorio (máx. 120 caracteres).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'El correo "' . $email . '" no es válido.';
    }
    if (!isset($MEMBER_ROLES[$role])) {
        $role = 'docente';
    }
    if (!license_is_usable($license)) {
        return 'La licencia no está activa.';
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Bloquea la licencia para evitar exceder cupos con solicitudes simultáneas
        $locked = db_one('SELECT seats FROM licenses WHERE id = ? FOR UPDATE', [$license['id']]);
        $used = (int) db_one('SELECT COUNT(*) c FROM license_members WHERE license_id = ?', [$license['id']])['c'];
        if ($used >= (int) $locked['seats']) {
            $pdo->rollBack();
            return 'No quedan cupos disponibles en esta licencia.';
        }
        if (db_one('SELECT id FROM license_members WHERE license_id = ? AND email = ?', [$license['id'], $email])) {
            $pdo->rollBack();
            return 'El correo ' . $email . ' ya está registrado en esta licencia.';
        }
        db_exec(
            'INSERT INTO license_members (license_id, name, email, role, area, access_code) VALUES (?, ?, ?, ?, ?, ?)',
            [$license['id'], $name, $email, $role, $area ?: null, strtoupper(bin2hex(random_bytes(4)))]
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    return null;
}
