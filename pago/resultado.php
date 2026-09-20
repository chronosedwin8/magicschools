<?php
/**
 * Resultado de un pago pendiente o rechazado (Efecty, PSE, pagos en revisión).
 * Consulta periódicamente el estado y redirige al portal cuando se aprueba.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$order = order_by_reference((string) ($_GET['ref'] ?? ''));
if (!$order || ((int) $order['user_id'] !== (int) $user['id'] && !is_admin($user))) {
    flash('error', 'No encontramos ese pedido.');
    redirect('portal/pedidos.php');
}
if ($order['status'] === 'approved') {
    $lic = db_one('SELECT id FROM licenses WHERE order_id = ?', [$order['id']]);
    redirect($lic ? 'portal/licencia.php?id=' . $lic['id'] : 'portal/');
}

$isEfecty = $order['payment_method'] === 'efecty';
$isPse = $order['payment_method'] === 'pse';
$pending = in_array($order['status'], ['pending', 'review'], true);
$message = mp_status_detail_message($order['status'], (string) $order['status_detail']);

$pageTitle = 'Estado del pago | ' . BRAND_NAME;
$bodyClass = 'page-simple page-pay';
$extraCss = 'assets/css/pay.css';
include __DIR__ . '/../includes/partials/head.php';
?>
<header class="pay-header">
    <div class="container pay-header-inner">
        <a href="<?= e(url('')) ?>" class="nav-brand"><?php include __DIR__ . '/../includes/partials/logo.php'; ?></a>
        <span class="pay-secure">🔒 Pago 100% seguro</span>
    </div>
</header>
<main class="result">
    <div class="container">
        <div class="card result-card">
            <?php if ($pending): ?>
                <div class="result-icon wait"><?= $isEfecty ? '💵' : '⏳' ?></div>
                <h2><?= $isEfecty ? 'Tu cupón de pago está listo' : ($isPse ? 'Completa el pago en tu banco' : 'Estamos procesando tu pago') ?></h2>
                <p class="muted"><?= e($isEfecty
                    ? 'Paga ' . money($order['amount']) . ' en cualquier punto Efecty presentando el cupón. Tu licencia se activará automáticamente cuando se acredite el pago.'
                    : $message) ?></p>
                <?php if ($order['payment_url']): ?>
                    <a class="btn btn-primary btn-lg" href="<?= e($order['payment_url']) ?>" target="_blank" rel="noopener">
                        <?= $isEfecty ? 'Ver e imprimir cupón' : ($isPse ? 'Ir a mi banco' : 'Continuar el pago') ?></a>
                <?php endif; ?>
                <p class="muted small mt-24" id="poll-note">Esta página se actualizará sola cuando confirmemos el pago.</p>
            <?php else: ?>
                <div class="result-icon bad">✕</div>
                <h2>El pago no se completó</h2>
                <p class="muted"><?= e($message) ?></p>
            <?php endif; ?>
            <div class="hero-ctas" style="justify-content:center">
                <a class="btn btn-outline" href="<?= e(url('pago/pagar.php?ref=' . urlencode($order['reference']))) ?>"><?= $pending ? 'Pagar con otro medio' : 'Intentar de nuevo' ?></a>
                <a class="btn btn-ghost" href="<?= e(url('portal/pedidos.php')) ?>">Ir a mis pedidos</a>
            </div>
            <?php $noticeCompact = true; include __DIR__ . '/../includes/partials/commerce_notice.php'; ?>
            <p class="muted small">Pedido <?= e($order['reference']) ?> · <?= e($order['plan_name']) ?> · <?= e(money($order['amount'])) ?></p>
        </div>
    </div>
</main>
<?php if ($pending): ?>
<script>
(function () {
    var url = <?= json_encode(url('pago/estado.php?flash=1&ref=' . urlencode($order['reference'])), JSON_UNESCAPED_SLASHES) ?>;
    var tries = 0;
    function poll() {
        tries++;
        fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
            if (d.status === 'approved' && d.redirect) { window.location.href = d.redirect; return; }
            if (d.status === 'rejected' || d.status === 'cancelled') { window.location.reload(); return; }
            if (tries < 120) setTimeout(poll, tries < 12 ? 5000 : 15000);
        }).catch(function () { if (tries < 120) setTimeout(poll, 15000); });
    }
    setTimeout(poll, 4000);
})();
</script>
<?php endif; ?>
</body>
</html>
