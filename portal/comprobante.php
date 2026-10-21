<?php
/**
 * Comprobante de pago de un pedido.
 *
 * Es el documento que el cliente descarga o imprime como soporte de su compra:
 * quién vendió, quién pagó, qué se pagó, cuándo y por cuánto. No reemplaza a la
 * factura electrónica de venta, que se expide por el sistema de facturación de
 * la empresa ante la DIAN; aquí se dice explícitamente.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();

$id = (int) ($_GET['id'] ?? 0);
$orden = is_admin($user)
    ? db_one('SELECT * FROM orders WHERE id = ?', [$id])
    : db_one('SELECT * FROM orders WHERE id = ? AND user_id = ?', [$id, $user['id']]);

if (!$orden || $orden['status'] !== 'approved') {
    flash('error', 'Ese comprobante no está disponible.');
    redirect('portal/pedidos.php');
}

$titular = db_one('SELECT * FROM users WHERE id = ?', [(int) $orden['user_id']]) ?: $user;
$licencia = db_one('SELECT * FROM licenses WHERE order_id = ?', [(int) $orden['id']]);
$plan = plan((string) $orden['plan_code']);

$pagador = trim((string) ($orden['billing_name'] ?? '')) !== ''
    ? (string) $orden['billing_name']
    : (string) $titular['name'];
$correoPagador = trim((string) ($orden['billing_email'] ?? '')) !== ''
    ? (string) $orden['billing_email']
    : (string) $titular['email'];
$neto = (float) ($orden['net_amount'] ?? 0);
$fechaPago = $orden['paid_at'] ?: $orden['created_at'];

$pageTitle = 'Comprobante ' . $orden['reference'];
$pageNoIndex = true;
$bodyClass = 'page-receipt';
$extraCss = ['assets/css/portal.css'];
include __DIR__ . '/../includes/partials/head.php';
?>
<div class="receipt-wrap" id="contenido">
    <div class="receipt-actions no-print">
        <a class="btn btn-outline btn-sm" href="<?= e(url('portal/pedidos.php')) ?>">← Mis pedidos</a>
        <button class="btn btn-primary btn-sm" onclick="window.print()">Imprimir o guardar en PDF</button>
    </div>

    <article class="receipt">
        <header class="receipt-head">
            <div>
                <div class="receipt-brand"><?= e(BRAND_NAME) ?></div>
                <p class="receipt-seller">
                    <?= e(COMPANY_NAME) ?><br>
                    <?php if (COMPANY_TAX_ID !== ''): ?>NIT <?= e(COMPANY_TAX_ID) ?><br><?php endif; ?>
                    <?= e(COMPANY_COUNTRY) ?> · <?= e(SUPPORT_EMAIL) ?>
                </p>
            </div>
            <div class="receipt-meta">
                <h1>Comprobante de pago</h1>
                <p><strong>N.º <?= e($orden['reference']) ?></strong></p>
                <p>Fecha de pago: <?= e(fecha($fechaPago)) ?></p>
                <p>Emitido: <?= e(fecha(date('Y-m-d'))) ?></p>
            </div>
        </header>

        <section class="receipt-parties">
            <div>
                <h2>Cliente</h2>
                <p>
                    <strong><?= e($pagador) ?></strong><br>
                    <?= e($correoPagador) ?><br>
                    <?php if (!empty($titular['institution'])): ?><?= e($titular['institution']) ?><br><?php endif; ?>
                    <?php if ($correoPagador !== $titular['email']): ?>
                        <span class="muted small">Cuenta del portal: <?= e($titular['email']) ?></span>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <h2>Forma de pago</h2>
                <?php
                // Las referencias que empiezan por MANUAL- las genera el sistema al confirmar
                // un pago a mano: son internas y no le dicen nada al cliente.
                $refPago = (string) ($orden['mp_payment_id'] ?? '');
                $refPago = str_starts_with($refPago, 'MANUAL-') ? '' : $refPago;
                ?>
                <p>
                    <?= e(PAYMENT_PROVIDER) ?><br>
                    <?php if ($refPago !== ''): ?>Referencia: <?= e($refPago) ?><br><?php endif; ?>
                    Estado: pagado
                </p>
            </div>
        </section>

        <table class="receipt-table">
            <thead>
                <tr><th>Concepto</th><th class="num">Valor</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?= e($orden['plan_name']) ?></strong>
                        <div class="muted small">
                            <?= (int) $orden['seats'] ?> usuarios ·
                            vigencia de <?= (int) ($plan['months'] ?? 12) ?> meses
                            <?php if ($licencia): ?>
                                · del <?= e(fecha($licencia['starts_at'])) ?> al <?= e(fecha($licencia['expires_at'])) ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="num"><?= e(money_short((float) $orden['amount'])) ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="total">
                    <td>Total pagado</td>
                    <td class="num"><?= e(money_short((float) $orden['amount'])) ?> <?= e($orden['currency']) ?></td>
                </tr>
                <?php if ($neto > 0): ?>
                    <tr>
                        <td class="muted small">Recibido el <?= e(fecha($fechaPago)) ?>, descontada la comisión de la pasarela</td>
                        <td class="num muted"><?= e(money_short($neto)) ?></td>
                    </tr>
                <?php endif; ?>
            </tfoot>
        </table>

        <?php if ($licencia): ?>
            <section class="receipt-license">
                <h2>Licencia activada</h2>
                <p><strong><?= e($licencia['license_key']) ?></strong> ·
                    <?= (int) $licencia['seats'] ?> usuarios ·
                    vence el <?= e(fecha($licencia['expires_at'])) ?></p>
            </section>
        <?php endif; ?>

        <footer class="receipt-foot">
            <p>Valores en pesos colombianos. El pago fue verificado por <?= e(COMPANY_NAME) ?>.</p>
            <p>Este documento es el <strong>comprobante del pago</strong> de la licencia y sirve como soporte de la
                compra. No es la factura electrónica de venta: esa se expide por el sistema de facturación de
                <?= e(COMPANY_NAME) ?> ante la DIAN y se envía al correo del cliente.</p>
            <p>Condiciones de la compra, derecho de retracto y política de reembolsos en
                <?= e(canonical_url('legal.php#comercio')) ?></p>
        </footer>
    </article>
</div>

<style>
    .page-receipt { background: #F4EFFF; }
    .receipt-wrap { max-width: 820px; margin: 0 auto; padding: 24px 16px 60px; }
    .receipt-actions { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
    .receipt { background: #fff; border-radius: 18px; padding: 42px 44px; box-shadow: var(--shadow-sm); color: var(--ink); }
    .receipt-head { display: flex; justify-content: space-between; gap: 30px; border-bottom: 2px solid var(--primary); padding-bottom: 20px; }
    .receipt-brand { font-family: var(--font-head); font-weight: 800; font-size: 1.5rem; color: var(--primary-700); }
    .receipt-seller { margin: 8px 0 0; font-size: .86rem; color: var(--ink-2); line-height: 1.5; }
    .receipt-meta { text-align: right; }
    .receipt-meta h1 { font-size: 1.2rem; margin: 0 0 6px; }
    .receipt-meta p { margin: 2px 0; font-size: .86rem; color: var(--ink-2); }
    .receipt-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin: 26px 0; }
    .receipt-parties h2, .receipt-license h2 { font-size: .78rem; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); margin: 0 0 6px; }
    .receipt-parties p { margin: 0; line-height: 1.6; font-size: .95rem; }
    .receipt-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .receipt-table th { text-align: left; font-size: .78rem; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); border-bottom: 1px solid var(--line); padding: 8px 0; }
    .receipt-table td { padding: 14px 0; border-bottom: 1px solid var(--line); vertical-align: top; }
    .receipt-table .num { text-align: right; white-space: nowrap; }
    .receipt-table tfoot .total td { font-family: var(--font-head); font-weight: 800; font-size: 1.1rem; border-bottom: 0; padding-top: 16px; }
    .receipt-table tfoot tr:not(.total) td { border-bottom: 0; padding-top: 0; }
    .receipt-license { margin-top: 26px; padding: 16px 18px; background: var(--primary-50); border-radius: 12px; }
    .receipt-license p { margin: 0; font-size: .95rem; }
    .receipt-foot { margin-top: 28px; border-top: 1px solid var(--line); padding-top: 16px; font-size: .8rem; color: var(--muted); }
    .receipt-foot p { margin: 0 0 6px; }
    @media print {
        .no-print { display: none !important; }
        .page-receipt { background: #fff; }
        .receipt-wrap { max-width: none; padding: 0; }
        .receipt { box-shadow: none; border-radius: 0; padding: 0; }
    }
    @media (max-width: 640px) {
        .receipt { padding: 24px 20px; }
        .receipt-head, .receipt-parties { grid-template-columns: 1fr; flex-direction: column; }
        .receipt-meta { text-align: left; }
    }
</style>
</body>
</html>
