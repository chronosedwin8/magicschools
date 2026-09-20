<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$licenses = user_licenses((int) $user['id']);
$active = 'licencias';
$pageTitle = 'Mis licencias';

include __DIR__ . '/_top.php';
?>
<div class="page-head">
    <div>
        <h1>Mis licencias</h1>
        <p class="muted">Todas las licencias adquiridas por <?= e($user['institution']) ?>.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('checkout.php')) ?>">+ Comprar licencia</a>
</div>

<?php if (!$licenses): ?>
    <div class="empty">
        <div class="empty-icon">🎟️</div>
        <h2>Aún no tienes licencias</h2>
        <p class="muted">Cuando tu pago sea aprobado, tu licencia aparecerá aquí automáticamente.</p>
        <a class="btn btn-primary" href="<?= e(url('')) ?>#precios">Ver planes y precios</a>
    </div>
<?php else: ?>
    <div class="license-grid">
        <?php foreach ($licenses as $l) include __DIR__ . '/_license_card.php'; ?>
    </div>
<?php endif; ?>
<?php include __DIR__ . '/_bottom.php'; ?>
