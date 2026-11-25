<?php
/**
 * Factura del pedido, separada del comprobante de pago.
 *
 * Lleva los datos que exige el artículo 6 del Real Decreto 1619/2012:
 * número y serie, fecha de expedición, fecha de la operación, expedidor con su
 * NIF y domicilio, destinatario, descripción de la operación, base imponible,
 * tipo impositivo y cuota repercutida.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();

$id = (int) ($_GET['id'] ?? 0);
$orden = is_admin($user)
    ? db_one('SELECT * FROM orders WHERE id = ?', [$id])
    : db_one('SELECT * FROM orders WHERE id = ? AND user_id = ?', [$id, $user['id']]);

if (!$orden || $orden['status'] !== 'approved') {
    flash('error', 'Esa factura no está disponible.');
    redirect('portal/pedidos.php');
}

$f = invoice_for_order((int) $orden['id']);
if (!$f) {
    flash(is_admin($user) ? 'warning' : 'info', is_admin($user)
        ? 'Este pedido todavía no tiene factura emitida. Emítela desde Administración > Facturación.'
        : 'La factura de este pedido aún no está disponible. Mientras tanto puedes descargar el comprobante de pago.');
    redirect(is_admin($user) ? 'admin/facturacion.php' : 'portal/pedidos.php');
}

$licencia = db_one('SELECT * FROM licenses WHERE order_id = ?', [(int) $orden['id']]);
$plan = plan((string) $orden['plan_code']);
$conIva = $f['tax_mode'] === 'repercutido' && (float) $f['tax_amount'] > 0;

$pageTitle = 'Factura ' . $f['numero_completo'];
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

    <article class="receipt invoice">
        <header class="receipt-head">
            <div>
                <div class="receipt-brand"><?= e(BRAND_NAME) ?></div>
                <p class="receipt-seller">
                    <strong><?= e($f['seller_name']) ?></strong><br>
                    NIF <?= e($f['seller_tax_id']) ?><br>
                    <?= nl2br(e($f['seller_address'])) ?><br>
                    <?php if (!empty($f['seller_registry'])): ?><?= e($f['seller_registry']) ?><br><?php endif; ?>
                    <?php if (!empty($f['seller_email'])): ?><?= e($f['seller_email']) ?><?php endif; ?>
                </p>
            </div>
            <div class="receipt-meta">
                <h1>Factura</h1>
                <p><strong>N.º <?= e($f['numero_completo']) ?></strong></p>
                <p>Fecha de expedición: <?= e(fecha($f['issued_at'])) ?></p>
                <p>Fecha de la operación: <?= e(fecha($f['operation_date'])) ?></p>
                <p>Comprobante de pago: <?= e($f['order_reference']) ?></p>
            </div>
        </header>

        <section class="receipt-parties">
            <div>
                <h2>Destinatario</h2>
                <p>
                    <strong><?= e($f['buyer_name']) ?></strong><br>
                    <?php // Una identificación sin dígitos (p. ej. "NIT -") no identifica a nadie: no se imprime ?>
                    <?php if (preg_match('/\d/', (string) $f['buyer_tax_id'])): ?><?= e($f['buyer_tax_id']) ?><br><?php endif; ?>
                    <?php if (!empty($f['buyer_address'])): ?><?= e($f['buyer_address']) ?><br><?php endif; ?>
                    <?php if (!empty($f['buyer_country'])): ?><?= e($f['buyer_country']) ?><br><?php endif; ?>
                    <?php if (!empty($f['buyer_email'])): ?><?= e($f['buyer_email']) ?><?php endif; ?>
                </p>
            </div>
            <div>
                <h2>Forma de pago</h2>
                <p>
                    <?= e(PAYMENT_PROVIDER) ?><br>
                    Pagada el <?= e(fecha($orden['paid_at'] ?: $orden['created_at'])) ?><br>
                    Estado: pagada
                </p>
            </div>
        </section>

        <table class="receipt-table invoice-table">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="num">Base imponible</th>
                    <th class="num"><?= $conIva ? 'IVA ' . rtrim(rtrim(number_format((float) $f['tax_rate'], 2, ',', '.'), '0'), ',') . ' %' : 'Impuesto' ?></th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?= e($f['concept']) ?></strong>
                        <div class="muted small">
                            <?= e($orden['plan_name']) ?> ·
                            <?= (int) $orden['seats'] ?> usuarios ·
                            vigencia de <?= (int) ($plan['months'] ?? 12) ?> meses
                            <?php if ($licencia): ?>
                                · del <?= e(fecha($licencia['starts_at'])) ?> al <?= e(fecha($licencia['expires_at'])) ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="num"><?= e(invoice_money((float) $f['tax_base'])) ?></td>
                    <td class="num"><?= $conIva ? e(invoice_money((float) $f['tax_amount'])) : '—' ?></td>
                    <td class="num"><?= e(invoice_money((float) $f['total'])) ?></td>
                </tr>
            </tbody>
        </table>

        <section class="invoice-totals">
            <dl>
                <div><dt>Base imponible</dt><dd><?= e(invoice_money((float) $f['tax_base'], $f['currency'])) ?></dd></div>
                <?php if ($conIva): ?>
                    <div><dt>Cuota de IVA (<?= e(rtrim(rtrim(number_format((float) $f['tax_rate'], 2, ',', '.'), '0'), ',')) ?> %)</dt>
                        <dd><?= e(invoice_money((float) $f['tax_amount'], $f['currency'])) ?></dd></div>
                <?php endif; ?>
                <div class="grand"><dt>Total factura</dt><dd><?= e(invoice_money((float) $f['total'], $f['currency'])) ?></dd></div>
            </dl>
        </section>

        <?php if (!empty($f['tax_note'])): ?>
            <p class="invoice-taxnote"><?= e($f['tax_note']) ?></p>
        <?php endif; ?>

        <?php if ($licencia): ?>
            <section class="receipt-license">
                <h2>Licencia entregada</h2>
                <p><strong><?= e($licencia['license_key']) ?></strong> ·
                    <?= (int) $licencia['seats'] ?> usuarios ·
                    vence el <?= e(fecha($licencia['expires_at'])) ?></p>
            </section>
        <?php endif; ?>

        <footer class="receipt-foot">
            <?php foreach (preg_split('/\r\n|\r|\n/', (string) $f['legal_note']) as $linea): if (trim($linea) === '') continue; ?>
                <p><?= e($linea) ?></p>
            <?php endforeach; ?>
            <p>Condiciones de contratación, derecho de desistimiento y política de reembolsos en
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
    .receipt-seller { margin: 8px 0 0; font-size: .86rem; color: var(--ink-2); line-height: 1.55; }
    .receipt-meta { text-align: right; }
    .receipt-meta h1 { font-size: 1.3rem; margin: 0 0 6px; letter-spacing: .04em; text-transform: uppercase; }
    .receipt-meta p { margin: 2px 0; font-size: .86rem; color: var(--ink-2); }
    .receipt-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin: 26px 0; }
    .receipt-parties h2, .receipt-license h2 { font-size: .78rem; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); margin: 0 0 6px; }
    .receipt-parties p { margin: 0; line-height: 1.6; font-size: .95rem; }
    .receipt-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .receipt-table th { text-align: left; font-size: .76rem; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); border-bottom: 1px solid var(--line); padding: 8px 0; }
    .receipt-table td { padding: 14px 0; border-bottom: 1px solid var(--line); vertical-align: top; }
    .receipt-table .num { text-align: right; white-space: nowrap; padding-left: 14px; }
    .invoice-totals { display: flex; justify-content: flex-end; margin-top: 18px; }
    .invoice-totals dl { margin: 0; min-width: 320px; }
    .invoice-totals div { display: flex; justify-content: space-between; gap: 24px; padding: 7px 0; }
    .invoice-totals dt, .invoice-totals dd { margin: 0; font-size: .95rem; color: var(--ink-2); }
    .invoice-totals .grand { border-top: 2px solid var(--primary); margin-top: 6px; padding-top: 12px; }
    .invoice-totals .grand dt, .invoice-totals .grand dd { font-family: var(--font-head); font-weight: 800; font-size: 1.1rem; color: var(--ink); }
    .invoice-taxnote { margin-top: 18px; padding: 12px 16px; background: var(--primary-50); border-radius: 10px; font-size: .86rem; color: var(--ink-2); }
    .receipt-license { margin-top: 24px; padding: 16px 18px; background: var(--primary-50); border-radius: 12px; }
    .receipt-license p { margin: 0; font-size: .95rem; }
    .receipt-foot { margin-top: 28px; border-top: 1px solid var(--line); padding-top: 16px; font-size: .78rem; color: var(--muted); line-height: 1.6; }
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
        .invoice-totals dl { min-width: 0; width: 100%; }
    }
</style>
</body>
</html>
