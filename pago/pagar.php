<?php
/**
 * Pantalla de pago: muestra el link de pago del plan y recoge el aviso del
 * cliente cuando ya pagó. La licencia la activa el administrador al confirmar
 * el pago en Administración > Pedidos y pagos.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$order = order_by_reference((string) ($_GET['ref'] ?? $_POST['ref'] ?? ''));
if (!$order || (int) $order['user_id'] !== (int) $user['id']) {
    flash('error', 'No encontramos ese pedido.');
    redirect('portal/pedidos.php');
}
if ($order['status'] === 'approved') {
    $lic = db_one('SELECT id FROM licenses WHERE order_id = ?', [$order['id']]);
    flash('info', 'Este pedido ya está pagado.');
    redirect($lic ? 'portal/licencia.php?id=' . $lic['id'] : 'portal/');
}

// El cliente avisa que ya pagó: el pedido queda en verificación
if (is_post()) {
    csrf_check();
    $ref = preg_replace('/[^\w\- ]/', '', trim((string) ($_POST['payment_reference'] ?? '')));
    $detail = 'El cliente informó el pago' . ($ref !== '' ? ' (ref. ' . mb_substr($ref, 0, 40) . ')' : '');
    db_exec(
        "UPDATE orders SET status = 'review', status_detail = ?, updated_at = NOW() WHERE id = ? AND status <> 'approved'",
        [mb_substr($detail, 0, 120), $order['id']]
    );
    app_log('pagos', 'Cliente informó pago', ['orden' => $order['reference'], 'usuario' => $user['email'], 'ref' => $ref]);
    flash('success', 'Gracias. Recibimos tu aviso de pago y estamos verificándolo.');
    redirect('pago/resultado.php?ref=' . urlencode($order['reference']));
}

$plan = plan($order['plan_code']);
$amount = (float) $order['amount'];
$link = trim((string) ($plan['payment_link'] ?? ''));

$pageTitle = 'Pago de tu licencia | ' . BRAND_NAME;
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

<main class="pay">
    <div class="container pay-grid">
        <section class="card pay-card">
            <h1>Completa tu pago</h1>
            <p class="muted">Pedido <strong><?= e($order['reference']) ?></strong> · <?= e($order['plan_name']) ?></p>
            <?= render_flashes() ?>

            <?php if ($link === ''): ?>
                <div class="alert alert-warning">
                    Este plan aún no tiene un link de pago configurado. Escríbenos a
                    <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a> y te enviamos el link de inmediato.
                </div>
            <?php else: ?>
                <ol class="pay-steps">
                    <li><strong>Paga con el botón de abajo.</strong> Se abre el link seguro de <?= e(PAYMENT_PROVIDER) ?>, donde puedes pagar con tarjeta, PSE o efectivo.</li>
                    <li><strong>Vuelve a esta página</strong> y avísanos que ya pagaste.</li>
                    <li><strong>Activamos tu licencia</strong> y la verás en tu portal, normalmente el mismo día hábil.</li>
                </ol>

                <a class="btn btn-primary btn-lg btn-block pay-link-btn" href="<?= e($link) ?>" target="_blank" rel="noopener">
                    Pagar <?= e(money($amount)) ?> <span aria-hidden="true">↗</span>
                </a>
                <p class="muted small center">El link se abre en una pestaña nueva de <?= e(PAYMENT_PROVIDER) ?>.</p>

                <form method="post" class="paid-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="ref" value="<?= e($order['reference']) ?>">
                    <h2>¿Ya pagaste?</h2>
                    <p class="muted small">Avísanos para verificar el pago y activar tu licencia cuanto antes.</p>
                    <label>Número de comprobante o referencia <span class="field-hint">(opcional)</span>
                        <input name="payment_reference" maxlength="40" placeholder="Ej. 123456789">
                    </label>
                    <button class="btn btn-outline btn-block">Ya realicé el pago</button>
                </form>
            <?php endif; ?>

            <p class="pay-foot-note">¿Necesitas factura, otra forma de pago o ayuda? Escríbenos a
                <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a> o al <?= e(SUPPORT_PHONE) ?>.</p>
        </section>

        <aside class="card pay-summary">
            <span class="plan-pill">Resumen del pedido</span>
            <h2><?= e($order['plan_name']) ?></h2>
            <p class="muted small"><?= e($user['institution']) ?></p>
            <div class="summary-row"><span>Usuarios incluidos</span><strong><?= (int) $order['seats'] ?></strong></div>
            <div class="summary-row"><span>Vigencia</span><strong><?= (int) ($plan['months'] ?? 12) ?> meses</strong></div>
            <div class="summary-row"><span>Referencia</span><strong><?= e($order['reference']) ?></strong></div>
            <div class="summary-row total"><span>Total a pagar</span><span><?= e(money($amount)) ?></span></div>
            <?php $noticeCompact = true; include __DIR__ . '/../includes/partials/commerce_notice.php'; ?>
            <a class="page-back small" href="<?= e(url('checkout.php?plan=' . $order['plan_code'])) ?>">← Cambiar plan o datos</a>
        </aside>
    </div>
</main>
<script src="<?= e(url('assets/js/main.js')) ?>" defer></script>
</body>
</html>
