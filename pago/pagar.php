<?php
/**
 * Pantalla de cobro propia (Checkout API de Mercado Pago).
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$order = order_by_reference((string) ($_GET['ref'] ?? ''));
if (!$order || (int) $order['user_id'] !== (int) $user['id']) {
    flash('error', 'No encontramos ese pedido.');
    redirect('portal/pedidos.php');
}
if ($order['status'] === 'approved') {
    $lic = db_one('SELECT id FROM licenses WHERE order_id = ?', [$order['id']]);
    flash('info', 'Este pedido ya está pagado.');
    redirect($lic ? 'portal/licencia.php?id=' . $lic['id'] : 'portal/');
}
if (!mp_enabled()) {
    redirect(mp_demo() ? 'pago/simulador.php?ref=' . urlencode($order['reference']) : 'portal/pedidos.php');
}

$plan = plan($order['plan_code']);
$amount = (float) $order['amount'];
$canPse = mp_method_allows('pse', $amount);
$canEfecty = mp_method_allows('efecty', $amount);
$banks = $canPse ? mp_pse_banks() : [];
$nameParts = preg_split('/\s+/', trim($user['name']), 2);
$docTypes = ['CC' => 'Cédula de ciudadanía', 'CE' => 'Cédula de extranjería', 'NIT' => 'NIT', 'Otro' => 'Otro'];
$userDocType = isset($docTypes[$user['document_type']]) ? $user['document_type'] : 'CC';
$cardDocType = $userDocType === 'NIT' ? 'CC' : $userDocType; // el titular de la tarjeta es una persona

$pageTitle = 'Pago seguro | ' . BRAND_NAME;
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

<main class="pay">
    <div class="container pay-grid">
        <section class="card pay-card">
            <h1>¿Cómo quieres pagar?</h1>
            <p class="muted">Pedido <strong><?= e($order['reference']) ?></strong> · <?= e($plan['name'] ?? $order['plan_name']) ?></p>

            <?php if ($order['payment_url'] && $order['status'] === 'pending'): ?>
                <div class="alert alert-warning">Tienes un pago pendiente para este pedido.
                    <a href="<?= e($order['payment_url']) ?>" target="_blank" rel="noopener">Continuar ese pago</a> o elige otro medio abajo.</div>
            <?php endif; ?>
            <?php if ($order['status'] === 'rejected'): ?>
                <div class="alert alert-error"><strong>El intento anterior no fue aprobado.</strong> <?= e(mp_status_detail_message('rejected', (string) $order['status_detail'])) ?></div>
            <?php endif; ?>

            <div class="pay-alert" id="pay-alert" role="alert" hidden></div>

            <div class="method-tabs" role="tablist">
                <button type="button" class="method-tab active" data-method="card" role="tab">
                    <span class="mt-icon">💳</span><span><strong>Tarjeta</strong><small>Crédito o débito</small></span>
                </button>
                <?php if ($canPse): ?>
                <button type="button" class="method-tab" data-method="pse" role="tab">
                    <span class="mt-icon">🏦</span><span><strong>PSE</strong><small>Débito desde tu banco</small></span>
                </button>
                <?php endif; ?>
                <?php if ($canEfecty): ?>
                <button type="button" class="method-tab" data-method="efecty" role="tab">
                    <span class="mt-icon">💵</span><span><strong>Efecty</strong><small>Pago en efectivo</small></span>
                </button>
                <?php endif; ?>
            </div>

            <!-- TARJETA ======================================================= -->
            <form id="form-card" class="pay-form" data-panel="card" novalidate>
                <div class="card-preview" aria-hidden="true">
                    <div class="cp-chip"></div>
                    <img class="cp-brand" id="card-brand" alt="" hidden>
                    <div class="cp-number" id="cp-number">•••• •••• •••• ••••</div>
                    <div class="cp-row"><span id="cp-name">NOMBRE DEL TITULAR</span><span>MM/AA</span></div>
                </div>

                <label>Número de la tarjeta
                    <div class="mp-field" id="mp-card-number"></div>
                    <span class="field-error" data-error="cardNumber"></span>
                </label>
                <div class="form-row">
                    <label>Vencimiento
                        <div class="mp-field" id="mp-expiration"></div>
                        <span class="field-error" data-error="expirationDate"></span>
                    </label>
                    <label>Código de seguridad
                        <div class="mp-field" id="mp-cvv"></div>
                        <span class="field-error" data-error="securityCode"></span>
                    </label>
                </div>
                <label>Nombre del titular (como aparece en la tarjeta)
                    <input name="cardholder" id="cardholder" autocomplete="cc-name" required maxlength="80">
                </label>
                <div class="form-row">
                    <label>Tipo de documento del titular
                        <select name="doc_type">
                            <?php foreach ($docTypes as $k => $v): ?><option value="<?= $k ?>" <?= $cardDocType === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label>Número de documento
                        <input name="doc_number" required maxlength="20" inputmode="numeric" value="<?= e($user['document_type'] === 'NIT' ? '' : $user['document_number']) ?>">
                    </label>
                </div>
                <label id="issuer-wrap" hidden>Banco emisor
                    <select name="issuer_id" id="issuer"></select>
                </label>
                <label id="installments-wrap">Cuotas
                    <select name="installments" id="installments" disabled><option value="1">Escribe el número de la tarjeta</option></select>
                </label>
                <label>Correo para el comprobante
                    <input type="email" name="email" required maxlength="190" value="<?= e($user['email']) ?>">
                </label>
                <button class="btn btn-primary btn-lg btn-block pay-btn" type="submit">Pagar <?= e(money($amount)) ?></button>
            </form>

            <!-- PSE ========================================================== -->
            <?php if ($canPse): ?>
            <form id="form-pse" class="pay-form" data-panel="pse" hidden novalidate>
                <p class="muted small">Serás dirigido a la página de tu banco para autorizar el débito. Al terminar volverás automáticamente aquí.</p>
                <label>Banco
                    <select name="bank" required>
                        <option value="">Selecciona tu banco</option>
                        <?php foreach ($banks as $b): ?><option value="<?= e($b['id']) ?>"><?= e($b['description']) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Tipo de persona
                    <select name="entity_type">
                        <option value="association" <?= $user['document_type'] === 'NIT' ? 'selected' : '' ?>>Persona jurídica (empresa / institución)</option>
                        <option value="individual" <?= $user['document_type'] !== 'NIT' ? 'selected' : '' ?>>Persona natural</option>
                    </select>
                </label>
                <div class="form-row">
                    <label>Nombres / razón social<input name="first_name" required maxlength="60" value="<?= e($nameParts[0] ?? '') ?>"></label>
                    <label>Apellidos<input name="last_name" maxlength="60" value="<?= e($nameParts[1] ?? '') ?>"></label>
                </div>
                <div class="form-row">
                    <label>Tipo de documento
                        <select name="doc_type"><?php foreach ($docTypes as $k => $v): ?><option value="<?= $k ?>" <?= $userDocType === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
                    </label>
                    <label>Número de documento<input name="doc_number" required maxlength="20" value="<?= e($user['document_number']) ?>"></label>
                </div>
                <div class="form-row">
                    <label>Teléfono<input name="phone" required maxlength="15" inputmode="tel" value="<?= e(preg_replace('/\D/', '', (string) $user['phone'])) ?>"></label>
                    <label>Correo<input type="email" name="email" required maxlength="190" value="<?= e($user['email']) ?>"></label>
                </div>
                <div class="form-row">
                    <label>Dirección (calle / carrera)<input name="street" required maxlength="80" placeholder="Calle 100"></label>
                    <label>Número<input name="street_number" required maxlength="20" placeholder="15-20"></label>
                </div>
                <div class="form-row">
                    <label>Barrio<input name="neighborhood" required maxlength="60"></label>
                    <label>Ciudad<input name="city" required maxlength="60" value="<?= e($user['city']) ?>"></label>
                </div>
                <div class="form-row">
                    <label>Departamento
                        <select name="department" required><option value="">Selecciona</option>
                            <?php foreach (co_departments() as $d): ?><option><?= e($d) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label>Código postal<input name="zip" maxlength="10" inputmode="numeric" placeholder="110111"></label>
                </div>
                <button class="btn btn-primary btn-lg btn-block pay-btn" type="submit">Ir a mi banco y pagar <?= e(money($amount)) ?></button>
            </form>
            <?php endif; ?>

            <!-- EFECTY ======================================================= -->
            <?php if ($canEfecty): ?>
            <form id="form-efecty" class="pay-form" data-panel="efecty" hidden novalidate>
                <div class="efecty-info">
                    <p><strong>¿Cómo funciona?</strong></p>
                    <ol>
                        <li>Genera tu cupón de pago.</li>
                        <li>Paga en efectivo en cualquier punto Efecty con el número del cupón.</li>
                        <li>Tu licencia se activa automáticamente cuando se acredite el pago (normalmente en pocas horas).</li>
                    </ol>
                </div>
                <div class="form-row">
                    <label>Nombres<input name="first_name" required maxlength="60" value="<?= e($nameParts[0] ?? '') ?>"></label>
                    <label>Apellidos<input name="last_name" maxlength="60" value="<?= e($nameParts[1] ?? '') ?>"></label>
                </div>
                <div class="form-row">
                    <label>Tipo de documento
                        <select name="doc_type"><?php foreach ($docTypes as $k => $v): ?><option value="<?= $k ?>" <?= $userDocType === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
                    </label>
                    <label>Número de documento<input name="doc_number" required maxlength="20" value="<?= e($user['document_number']) ?>"></label>
                </div>
                <label>Correo<input type="email" name="email" required maxlength="190" value="<?= e($user['email']) ?>"></label>
                <button class="btn btn-primary btn-lg btn-block pay-btn" type="submit">Generar cupón de pago</button>
            </form>
            <?php endif; ?>

            <p class="pay-foot-note">🔒 Tus datos viajan cifrados. Los datos de tu tarjeta no se almacenan en nuestros servidores.</p>
            <?php $noticeCompact = false; include __DIR__ . '/../includes/partials/commerce_notice.php'; ?>
        </section>

        <aside class="card pay-summary">
            <span class="plan-pill">Resumen del pedido</span>
            <h2><?= e($order['plan_name']) ?></h2>
            <p class="muted small"><?= e($user['institution']) ?></p>
            <div class="summary-row"><span>Usuarios incluidos</span><strong><?= (int) $order['seats'] ?></strong></div>
            <div class="summary-row"><span>Vigencia</span><strong><?= (int) ($plan['months'] ?? 12) ?> meses</strong></div>
            <div class="summary-row"><span>Referencia</span><strong><?= e($order['reference']) ?></strong></div>
            <div class="summary-row total"><span>Total a pagar</span><span><?= e(money($amount)) ?></span></div>
            <div class="accepted">
                <span>VISA</span><span>Mastercard</span><span>AMEX</span><span>Diners</span>
                <?php if ($canPse): ?><span>PSE</span><?php endif; ?><?php if ($canEfecty): ?><span>Efecty</span><?php endif; ?>
            </div>
            <p class="muted small">En el extracto verás el cobro como <strong><?= e(mp_statement_descriptor()) ?></strong>.</p>
            <?php $noticeCompact = true; include __DIR__ . '/../includes/partials/commerce_notice.php'; ?>
            <a class="page-back small" href="<?= e(url('checkout.php?plan=' . $order['plan_code'])) ?>">← Cambiar plan o datos</a>
        </aside>
    </div>
</main>

<!-- Verificación 3-D Secure del banco -->
<div class="modal" id="threeds-modal" hidden>
    <div class="modal-box">
        <h3>Verificación de tu banco</h3>
        <p class="muted small">Confirma la compra en la ventana de tu banco. No cierres esta página.</p>
        <iframe name="threeds-frame" id="threeds-frame" title="Verificación del banco"></iframe>
    </div>
</div>

<!-- Procesando -->
<div class="modal" id="processing-modal" hidden>
    <div class="modal-box center">
        <div class="spinner"></div>
        <h3 id="processing-text">Procesando tu pago…</h3>
        <p class="muted small">Esto puede tomar unos segundos. No cierres ni recargues la página.</p>
    </div>
</div>

<script>
window.PAY = <?= json_encode([
    'publicKey' => MP_PUBLIC_KEY,
    'amount'    => (string) (int) round($amount),
    'ref'       => $order['reference'],
    'csrf'      => csrf_token(),
    'process'   => url('pago/procesar.php'),
    'status'    => url('pago/estado.php'),
], JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="https://www.mercadopago.com/v2/security.js" view="checkout"></script>
<script src="https://sdk.mercadopago.com/js/v2"></script>
<script src="<?= e(url('assets/js/pago.js')) ?>?v=<?= filemtime(__DIR__ . '/../assets/js/pago.js') ?>"></script>
</body>
</html>
