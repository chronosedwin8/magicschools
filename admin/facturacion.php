<?php
/**
 * Facturación: datos fiscales del expedidor y emisión de facturas.
 *
 * La factura se emite una sola vez por pedido y después no cambia. Lo que se
 * configura aquí solo afecta a las facturas que se emitan de ahora en adelante.
 */
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../includes/invoices.php';

if (is_post()) {
    csrf_check();
    $accion = (string) ($_POST['action'] ?? '');

    if ($accion === 'ajustes') {
        $campos = [
            'invoice_serie'           => mb_substr(trim(ai_normalize_text($_POST['serie'] ?? 'A')), 0, 10),
            'invoice_seller_name'     => mb_substr(trim(ai_normalize_text($_POST['seller_name'] ?? '')), 0, 160),
            'invoice_seller_tax_id'   => mb_substr(trim(ai_normalize_text($_POST['seller_tax_id'] ?? '')), 0, 30),
            'invoice_seller_address'  => mb_substr(trim(ai_normalize_text($_POST['seller_address'] ?? '')), 0, 255),
            'invoice_seller_registry' => mb_substr(trim(ai_normalize_text($_POST['seller_registry'] ?? '')), 0, 255),
            'invoice_seller_email'    => mb_substr(trim(ai_normalize_text($_POST['seller_email'] ?? '')), 0, 190),
            'invoice_concept'         => mb_substr(trim(ai_normalize_text($_POST['concept'] ?? '')), 0, 255),
            'invoice_tax_mode'        => in_array($_POST['tax_mode'] ?? '', ['repercutido', 'no_sujeta', 'exenta'], true) ? $_POST['tax_mode'] : 'repercutido',
            'invoice_tax_rate'        => (string) max(0, min(100, (float) str_replace(',', '.', (string) ($_POST['tax_rate'] ?? '21')))),
            'invoice_tax_note'        => mb_substr(trim(ai_normalize_text($_POST['tax_note'] ?? '')), 0, 255),
            'invoice_legal_note'      => mb_substr(trim(ai_normalize_text($_POST['legal_note'] ?? '')), 0, 2000),
        ];
        try {
            foreach ($campos as $clave => $valor) {
                setting_save($clave, $valor, (int) $user['id']);
            }
            app_log('facturacion', 'Ajustes de facturación guardados', ['admin' => $user['id']]);
            flash('success', 'Datos de facturación guardados. Afectan a las próximas facturas, no a las ya emitidas.');
        } catch (Throwable $e) {
            app_log('facturacion', 'No se pudieron guardar los ajustes', ['error' => $e->getMessage()]);
            flash('error', 'No se pudieron guardar los datos de facturación. Revisa el registro de la aplicación.');
        }
        redirect('admin/facturacion.php');
    }

    if ($accion === 'emitir') {
        $orden = db_one('SELECT * FROM orders WHERE id = ?', [(int) ($_POST['order_id'] ?? 0)]);
        if (!$orden) {
            flash('error', 'Pedido no encontrado.');
            redirect('admin/facturacion.php');
        }
        $cliente = db_one('SELECT * FROM users WHERE id = ?', [(int) $orden['user_id']]);
        $r = invoice_issue($orden, [
            'buyer_name'    => $_POST['buyer_name'] ?? ($orden['billing_name'] ?: $cliente['institution']),
            'buyer_tax_id'  => $_POST['buyer_tax_id'] ?? '',
            'buyer_address' => $_POST['buyer_address'] ?? '',
            'buyer_country' => $_POST['buyer_country'] ?? '',
            'buyer_email'   => $_POST['buyer_email'] ?? ($orden['billing_email'] ?: $cliente['email']),
            'concept'       => $_POST['concept'] ?? '',
            'tax_mode'      => $_POST['tax_mode'] ?? null,
            'tax_rate'      => $_POST['tax_rate'] ?? null,
        ], (int) $user['id']);

        flash($r['ok'] ? 'success' : 'error', $r['ok']
            ? 'Factura ' . $r['invoice']['numero_completo'] . ' emitida. El cliente ya la ve en su portal.'
            : $r['error']);
        redirect('admin/facturacion.php');
    }
}

$cfg = invoice_config();
$faltan = invoice_config_missing($cfg);

$pendientes = db_all(
    'SELECT o.*, u.name AS user_name, u.email AS user_email, u.institution, u.document_type, u.document_number, u.city
       FROM orders o JOIN users u ON u.id = o.user_id
       LEFT JOIN invoices i ON i.order_id = o.id
      WHERE o.status = "approved" AND i.id IS NULL AND o.amount > 0
      ORDER BY o.paid_at DESC, o.id DESC'
);
$emitidas = db_all(
    'SELECT i.*, o.reference FROM invoices i JOIN orders o ON o.id = i.order_id
      ORDER BY i.serie, i.numero DESC LIMIT 50'
);

$active = 'facturacion';
$pageTitle = 'Facturación';
include __DIR__ . '/../portal/_top.php';
?>
<div class="page-head">
    <div>
        <h1>Facturación</h1>
        <p class="muted">Datos fiscales del expedidor y emisión de facturas con numeración correlativa.</p>
    </div>
</div>

<?php if ($faltan): ?>
    <p class="alert alert-warning">Para poder emitir facturas falta configurar <strong><?= e(implode(', ', $faltan)) ?></strong>.</p>
<?php endif; ?>

<section class="panel">
    <h2>Pedidos pagados sin factura</h2>
    <?php if (!$pendientes): ?>
        <p class="muted">Todos los pedidos pagados ya tienen su factura.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Pedido</th><th>Cliente</th><th>Pagado</th><th>Importe</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($pendientes as $o): ?>
                    <tr>
                        <td><strong><?= e($o['reference']) ?></strong><small class="block muted"><?= e($o['plan_name']) ?></small></td>
                        <td><?= e($o['billing_name'] ?: $o['user_name']) ?><small class="block muted"><?= e($o['institution']) ?></small></td>
                        <td class="nowrap"><?= e(fecha($o['paid_at'] ?: $o['created_at'])) ?></td>
                        <td class="nowrap"><?= e(money_short((float) $o['amount'])) ?></td>
                        <td>
                            <details class="edit-pop">
                                <summary class="btn btn-primary btn-sm">Emitir factura</summary>
                                <form method="post" class="pop"><?= csrf_field() ?>
                                    <input type="hidden" name="action" value="emitir">
                                    <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                    <label>Razón social del destinatario
                                        <input name="buyer_name" maxlength="160" required
                                               value="<?= e($o['institution'] ?: ($o['billing_name'] ?: $o['user_name'])) ?>"></label>
                                    <label>Identificación fiscal <span class="field-hint">(NIT, NIF, RUT…)</span>
                                        <input name="buyer_tax_id" maxlength="40"
                                               value="<?= e(trim(($o['document_type'] ?? '') . ' ' . ($o['document_number'] ?? ''))) ?>"></label>
                                    <label>Domicilio del destinatario
                                        <input name="buyer_address" maxlength="255" value="<?= e((string) $o['city']) ?>"></label>
                                    <label>País<input name="buyer_country" maxlength="60" value="Colombia"></label>
                                    <label>Correo<input name="buyer_email" type="email" maxlength="190"
                                                        value="<?= e($o['billing_email'] ?: $o['user_email']) ?>"></label>
                                    <label>Descripción de la operación
                                        <input name="concept" maxlength="255" value="<?= e($cfg['concept']) ?>"></label>
                                    <div class="form-row">
                                        <label>Régimen
                                            <select name="tax_mode">
                                                <option value="repercutido" <?= $cfg['tax_mode'] === 'repercutido' ? 'selected' : '' ?>>IVA repercutido</option>
                                                <option value="no_sujeta" <?= $cfg['tax_mode'] === 'no_sujeta' ? 'selected' : '' ?>>No sujeta</option>
                                                <option value="exenta" <?= $cfg['tax_mode'] === 'exenta' ? 'selected' : '' ?>>Exenta</option>
                                            </select></label>
                                        <label>Tipo de IVA (%)
                                            <input name="tax_rate" inputmode="decimal" value="<?= e((string) $cfg['tax_rate']) ?>"></label>
                                    </div>
                                    <p class="small muted">El importe cobrado (<?= e(money_short((float) $o['amount'])) ?>)
                                        se toma como total con impuesto incluido: la base y la cuota se calculan hacia atrás.</p>
                                    <button class="btn btn-primary btn-sm btn-block">Emitir y numerar</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Facturas emitidas</h2>
    <?php if (!$emitidas): ?>
        <p class="muted">Todavía no has emitido ninguna factura.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Número</th><th>Fecha</th><th>Destinatario</th><th>Base</th><th>IVA</th><th>Total</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($emitidas as $i): ?>
                    <tr>
                        <td><strong><?= e($i['numero_completo']) ?></strong><small class="block muted"><?= e($i['order_reference']) ?></small></td>
                        <td class="nowrap"><?= e(fecha($i['issued_at'])) ?></td>
                        <td><?= e($i['buyer_name']) ?><small class="block muted"><?= e((string) $i['buyer_tax_id']) ?></small></td>
                        <td class="nowrap"><?= e(invoice_money((float) $i['tax_base'])) ?></td>
                        <td class="nowrap"><?= (float) $i['tax_amount'] > 0 ? e(invoice_money((float) $i['tax_amount'])) : '—' ?></td>
                        <td class="nowrap"><strong><?= e(invoice_money((float) $i['total'], $i['currency'])) ?></strong></td>
                        <td><a class="btn btn-outline btn-sm" target="_blank" rel="noopener"
                               href="<?= e(url('portal/factura.php?id=' . (int) $i['order_id'])) ?>">Ver</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Datos del expedidor</h2>
    <p class="muted small">Son los que aparecen en la factura. Solo afectan a las facturas que se emitan después de guardarlos.</p>
    <form method="post" class="form-stack">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="ajustes">
        <div class="form-row">
            <label>Razón social<input name="seller_name" maxlength="160" required value="<?= e($cfg['seller_name']) ?>"
                                      placeholder="Ej. Grupo Logic Europa, S.L."></label>
            <label>NIF<input name="seller_tax_id" maxlength="30" required value="<?= e($cfg['seller_tax_id']) ?>"
                             placeholder="Ej. B12345678"></label>
        </div>
        <label>Domicilio fiscal<input name="seller_address" maxlength="255" required value="<?= e($cfg['seller_address']) ?>"
                                      placeholder="Calle, número, código postal, ciudad, España"></label>
        <div class="form-row">
            <label>Datos registrales <span class="field-hint">(opcional)</span>
                <input name="seller_registry" maxlength="255" value="<?= e($cfg['seller_registry']) ?>"
                       placeholder="Registro Mercantil de…, tomo, folio, hoja"></label>
            <label>Correo de facturación<input name="seller_email" type="email" maxlength="190" value="<?= e($cfg['seller_email']) ?>"></label>
        </div>
        <div class="form-row">
            <label>Serie<input name="serie" maxlength="10" required value="<?= e($cfg['serie']) ?>"></label>
            <label>Régimen por defecto
                <select name="tax_mode">
                    <option value="repercutido" <?= $cfg['tax_mode'] === 'repercutido' ? 'selected' : '' ?>>IVA repercutido</option>
                    <option value="no_sujeta" <?= $cfg['tax_mode'] === 'no_sujeta' ? 'selected' : '' ?>>No sujeta (reglas de localización)</option>
                    <option value="exenta" <?= $cfg['tax_mode'] === 'exenta' ? 'selected' : '' ?>>Exenta</option>
                </select></label>
            <label>Tipo de IVA (%)<input name="tax_rate" inputmode="decimal" value="<?= e((string) $cfg['tax_rate']) ?>"></label>
        </div>
        <label>Descripción por defecto de la operación<input name="concept" maxlength="255" value="<?= e($cfg['concept']) ?>"></label>
        <label>Mención fiscal <span class="field-hint">(obligatoria si la operación no lleva IVA; vacío = texto automático)</span>
            <input name="tax_note" maxlength="255" value="<?= e($cfg['tax_note']) ?>"></label>
        <label>Texto legal del pie
            <textarea name="legal_note" rows="6" maxlength="2000"><?= e($cfg['legal_note']) ?></textarea></label>
        <button class="btn btn-primary">Guardar datos de facturación</button>
    </form>
</section>

<section class="panel">
    <h2>Lo que este módulo no cubre</h2>
    <ul class="check-list">
        <li>No es un sistema de facturación verificable: no encadena los registros con huella, no los envía a la AEAT
            ni imprime el QR de verificación que exige el Real Decreto 1007/2023 (Veri*factu).</li>
        <li>No decide la tributación de cada venta. El régimen y el tipo los fija quien lleva tu contabilidad.</li>
        <li>No lleva los libros registro ni presenta modelos tributarios.</li>
    </ul>
    <p class="muted small">Si tu asesoría ya emite las facturas oficiales, usa esto solo como copia para el cliente y
        mantén la numeración separada de la de tu programa de facturación.</p>
</section>
<?php include __DIR__ . '/../portal/_bottom.php'; ?>
