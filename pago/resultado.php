<?php
/**
 * Estado del pedido tras usar el link de pago.
 * La licencia se activa cuando el administrador confirma el pago.
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

$enRevision = $order['status'] === 'review';
$cancelado = in_array($order['status'], ['cancelled', 'rejected'], true);

$pageTitle = 'Estado de tu pedido | ' . BRAND_NAME;
$bodyClass = 'page-simple page-pay';
$extraCss = 'assets/css/pay.css';
include __DIR__ . '/../includes/partials/head.php';
?>
<header class="pay-header">
    <div class="container pay-header-inner">
        <a href="<?= e(url('')) ?>" class="nav-brand"><?php include __DIR__ . '/../includes/partials/logo.php'; ?></a>
        <span class="pay-secure">🔒 Pago seguro</span>
    </div>
</header>
<main class="result">
    <div class="container">
        <div class="card result-card">
            <?= render_flashes() ?>
            <?php if ($enRevision): ?>
                <div class="result-icon wait">⏳</div>
                <h2>Estamos verificando tu pago</h2>
                <p class="muted">Recibimos tu aviso. En cuanto confirmemos el pago con <?= e(PAYMENT_PROVIDER) ?> activaremos tu licencia
                    y la verás en tu portal. Normalmente ocurre el mismo día hábil y te avisamos a <strong><?= e($user['email']) ?></strong>.</p>
            <?php elseif ($cancelado): ?>
                <div class="result-icon bad">✕</div>
                <h2>Este pedido está <?= $order['status'] === 'cancelled' ? 'cancelado' : 'sin completar' ?></h2>
                <p class="muted">Puedes generar el pago de nuevo o escribirnos si necesitas ayuda.</p>
            <?php else: ?>
                <div class="result-icon wait">💳</div>
                <h2>Tu pedido está pendiente de pago</h2>
                <p class="muted">Usa el link de pago para completar la compra. Cuando pagues, avísanos desde la misma pantalla.</p>
            <?php endif; ?>

            <div class="hero-ctas" style="justify-content:center">
                <a class="btn btn-primary" href="<?= e(url('pago/pagar.php?ref=' . urlencode($order['reference']))) ?>">
                    <?= $enRevision ? 'Ver el link de pago' : 'Ir al link de pago' ?></a>
                <a class="btn btn-ghost" href="<?= e(url('portal/pedidos.php')) ?>">Ir a mis pedidos</a>
            </div>

            <p class="muted small">Pedido <?= e($order['reference']) ?> · <?= e($order['plan_name']) ?> · <?= e(money($order['amount'])) ?></p>
            <p class="muted small">¿Dudas con tu pago? Escríbenos a <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a> o al <?= e(SUPPORT_PHONE) ?>.</p>
        </div>
    </div>
</main>
<script src="<?= e(url('assets/js/main.js')) ?>" defer></script>
</body>
</html>
