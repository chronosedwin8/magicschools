<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$licenses = user_licenses((int) $user['id']);
$active = 'inicio';
$pageTitle = 'Inicio';

$totalSeats = $usedSeats = $activeCount = 0;
foreach ($licenses as $l) {
    if (license_is_usable($l)) {
        $activeCount++;
        $totalSeats += (int) $l['seats'];
        $usedSeats += (int) $l['used_seats'];
    }
}
$pending = db_all('SELECT * FROM orders WHERE user_id = ? AND status IN ("pending","review") ORDER BY created_at DESC LIMIT 3', [$user['id']]);
$firstName = explode(' ', trim($user['name']))[0];

include __DIR__ . '/_top.php';
?>
<div class="page-head">
    <div>
        <h1>¡Hola, <?= e($firstName) ?>! 👋</h1>
        <p class="muted">Este es el resumen de las licencias de <?= e($user['institution']) ?>.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('checkout.php')) ?>">+ Comprar licencia</a>
</div>

<div class="kpis">
    <div class="kpi"><span class="kpi-icon c-purple">🎟️</span><div><small>Licencias activas</small><strong><?= $activeCount ?></strong></div></div>
    <div class="kpi"><span class="kpi-icon c-pink">👥</span><div><small>Cupos totales</small><strong><?= $totalSeats ?></strong></div></div>
    <div class="kpi"><span class="kpi-icon c-green">✅</span><div><small>Usuarios asignados</small><strong><?= $usedSeats ?></strong></div></div>
    <div class="kpi"><span class="kpi-icon c-orange">🪑</span><div><small>Cupos disponibles</small><strong><?= max(0, $totalSeats - $usedSeats) ?></strong></div></div>
</div>

<?php foreach ($pending as $o): ?>
    <div class="alert alert-warning">
        La orden <strong><?= e($o['reference']) ?></strong> (<?= e($o['plan_name']) ?>, <?= e(money($o['amount'])) ?>) está
        <?= $o['status'] === 'review' ? 'en verificación' : 'pendiente de pago' ?>.
        <a href="<?= e(url('portal/pedidos.php')) ?>">Ver pedidos</a>
    </div>
<?php endforeach; ?>

<?php if (!$licenses): ?>
    <div class="empty">
        <div class="empty-icon">🎟️</div>
        <h2>Aún no tienes licencias activas</h2>
        <p class="muted">Adquiere una licencia para empezar a invitar a los docentes de tu institución.</p>
        <a class="btn btn-primary" href="<?= e(url('')) ?>#precios">Ver planes y precios</a>
    </div>
<?php else: ?>
    <h2 class="section-title">Tus licencias</h2>
    <div class="license-grid">
        <?php foreach ($licenses as $l) include __DIR__ . '/_license_card.php'; ?>
    </div>
<?php endif; ?>

<div class="help-grid">
    <div class="panel">
        <h3>🚀 Primeros pasos</h3>
        <ol class="steps-list">
            <li class="<?= $licenses ? 'done' : '' ?>">Adquiere tu licencia</li>
            <li class="<?= $usedSeats > 0 ? 'done' : '' ?>">Agrega a tus docentes y directivos</li>
            <li>Comparte el código de licencia con tu equipo</li>
            <li>Agenda la capacitación de arranque con soporte</li>
        </ol>
    </div>
    <div class="panel">
        <h3>💬 ¿Necesitas ayuda?</h3>
        <p class="muted">Nuestro equipo te acompaña en la activación y en la formación de tus docentes.</p>
        <p><strong>Correo:</strong> <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a><br><strong>Teléfono / WhatsApp:</strong> <?= e(SUPPORT_PHONE) ?></p>
    </div>
</div>
<?php include __DIR__ . '/_bottom.php'; ?>
