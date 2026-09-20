<?php
require_once __DIR__ . '/_init.php';

$stats = db_one(
    "SELECT
        (SELECT COALESCE(SUM(amount),0) FROM orders WHERE status = 'approved') AS revenue,
        (SELECT COALESCE(SUM(amount),0) FROM orders WHERE status = 'approved' AND paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')) AS revenue_month,
        (SELECT COUNT(*) FROM orders WHERE status = 'approved') AS orders_ok,
        (SELECT COUNT(*) FROM orders WHERE status IN ('pending','review')) AS orders_pending,
        (SELECT COUNT(*) FROM licenses WHERE status = 'active' AND expires_at > NOW()) AS licenses_active,
        (SELECT COALESCE(SUM(seats),0) FROM licenses WHERE status = 'active' AND expires_at > NOW()) AS seats_total,
        (SELECT COUNT(*) FROM license_members m JOIN licenses l ON l.id = m.license_id WHERE l.status = 'active' AND l.expires_at > NOW()) AS seats_used,
        (SELECT COUNT(*) FROM users WHERE role = 'customer') AS customers,
        (SELECT COUNT(*) FROM contact_requests WHERE created_at >= NOW() - INTERVAL 30 DAY) AS contacts_30d,
        (SELECT COUNT(*) FROM licenses WHERE status = 'active' AND expires_at BETWEEN NOW() AND NOW() + INTERVAL 30 DAY) AS expiring"
);
$recent = db_all(
    'SELECT o.*, u.name AS user_name, u.institution FROM orders o JOIN users u ON u.id = o.user_id
      ORDER BY o.created_at DESC LIMIT 8'
);
$byPlan = db_all("SELECT plan_name, COUNT(*) n, SUM(amount) total FROM orders WHERE status = 'approved' GROUP BY plan_name");

$active = 'dashboard';
$pageTitle = 'Resumen';
include __DIR__ . '/../portal/_top.php';
?>
<div class="page-head">
    <div>
        <h1>Panel de administración</h1>
        <p class="muted">Visión general de ventas, licencias y clientes de <?= e(BRAND_NAME) ?>.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('admin/clientes.php')) ?>">+ Asignar licencia</a>
</div>

<div class="kpis">
    <div class="kpi"><span class="kpi-icon c-green">💰</span><div><small>Ventas totales</small><strong class="kpi-sm"><?= e(money($stats['revenue'])) ?></strong><small>Este mes: <?= e(money_short($stats['revenue_month'])) ?></small></div></div>
    <div class="kpi"><span class="kpi-icon c-purple">🧾</span><div><small>Pedidos aprobados</small><strong><?= (int) $stats['orders_ok'] ?></strong><small><?= (int) $stats['orders_pending'] ?> pendientes</small></div></div>
    <div class="kpi"><span class="kpi-icon c-pink">🎟️</span><div><small>Licencias activas</small><strong><?= (int) $stats['licenses_active'] ?></strong><small><?= (int) $stats['expiring'] ?> vencen en 30 días</small></div></div>
    <div class="kpi"><span class="kpi-icon c-orange">👥</span><div><small>Cupos usados</small><strong><?= (int) $stats['seats_used'] ?>/<?= (int) $stats['seats_total'] ?></strong><small><?= (int) $stats['customers'] ?> clientes · <?= (int) $stats['contacts_30d'] ?> contactos (30 d)</small></div></div>
</div>

<div class="two-col">
    <section class="panel">
        <h2>Ventas por plan</h2>
        <?php if (!$byPlan): ?><p class="muted">Aún no hay ventas aprobadas.</p><?php endif; ?>
        <?php foreach ($byPlan as $p): ?>
            <div class="summary-row"><span><?= e($p['plan_name']) ?> <small class="muted">(<?= (int) $p['n'] ?>)</small></span><strong><?= e(money_short($p['total'])) ?></strong></div>
        <?php endforeach; ?>
        <p class="muted small mt-24">Mercado Pago: <?= mp_enabled() ? (MP_SANDBOX ? 'modo pruebas' : '<strong>producción</strong>') : 'sin credenciales' ?> · Webhook: <?= MP_WEBHOOK_SECRET !== '' ? 'firma validada' : 'sin clave' ?></p>
    </section>
    <section class="panel panel-wide">
        <div class="panel-head"><h2>Pedidos recientes</h2><a class="btn btn-outline btn-sm" href="<?= e(url('admin/pedidos.php')) ?>">Ver todos</a></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Orden</th><th>Cliente</th><th>Plan</th><th>Valor</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $o): [$lbl, $cls] = $ORDER_STATUS[$o['status']] ?? [$o['status'], 'muted']; ?>
                    <tr>
                        <td><strong><?= e($o['reference']) ?></strong><small class="block muted"><?= fecha($o['created_at'], true) ?></small></td>
                        <td><?= e($o['institution']) ?><small class="block muted"><?= e($o['user_name']) ?></small></td>
                        <td><?= e($o['plan_name']) ?></td>
                        <td class="nowrap"><?= e(money_short($o['amount'])) ?></td>
                        <td><span class="badge badge-<?= e($cls) ?>"><?= e($lbl) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?><tr><td colspan="5" class="muted">Sin pedidos.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php include __DIR__ . '/../portal/_bottom.php'; ?>
