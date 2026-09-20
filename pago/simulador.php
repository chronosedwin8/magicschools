<?php
/**
 * Simulador de pago local. Solo funciona con DEMO_MODE = true y sin credenciales
 * de Mercado Pago. Permite probar todo el flujo (pago -> portal) sin cobrar.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

if (!mp_demo()) {
    http_response_code(404);
    exit('No disponible.');
}

$user = require_login();
$order = order_by_reference((string) ($_GET['ref'] ?? $_POST['ref'] ?? ''));
if (!$order || (int) $order['user_id'] !== (int) $user['id']) {
    http_response_code(404);
    exit('Orden no encontrada.');
}

if (is_post()) {
    csrf_check();
    $status = in_array($_POST['result'] ?? '', ['approved', 'rejected', 'pending'], true) ? $_POST['result'] : 'rejected';
    $fakeId = (string) random_int(1000000000, 9999999999);
    apply_payment([
        'id'                 => $fakeId,
        'status'             => $status,
        'status_detail'      => $status === 'approved' ? 'accredited' : ($status === 'pending' ? 'pending_waiting_payment' : 'cc_rejected_other_reason'),
        'external_reference' => $order['reference'],
        'transaction_amount' => (float) $order['amount'],
        'currency_id'        => $order['currency'],
        'payment_method_id'  => $_POST['method'] ?? 'visa',
        'simulated'          => true,
    ]);
    $map = ['approved' => 'exito', 'pending' => 'pendiente', 'rejected' => 'fallo'];
    redirect('pago/retorno.php?estado=' . $map[$status] . '&external_reference=' . urlencode($order['reference']) . '&payment_id=' . $fakeId . '&status=' . $status);
}

$pageTitle = 'Simulador de pago | ' . BRAND_NAME;
$bodyClass = 'page-simple';
include __DIR__ . '/../includes/partials/head.php';
?>
<main class="result">
    <div class="container">
        <div class="card result-card">
            <div class="demo-banner">🧪 <strong>Simulador de Mercado Pago</strong> — este paso reemplaza la pasarela real mientras no haya credenciales configuradas en <code>config.php</code>.</div>
            <div class="result-icon wait">💳</div>
            <h2>Pagar <?= e(money($order['amount'])) ?></h2>
            <p class="muted"><?= e($order['plan_name']) ?> · Orden <?= e($order['reference']) ?></p>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="ref" value="<?= e($order['reference']) ?>">
                <label>Medio de pago
                    <select name="method">
                        <option value="visa">Tarjeta Visa</option>
                        <option value="master">Tarjeta Mastercard</option>
                        <option value="pse">PSE</option>
                        <option value="efecty">Efecty</option>
                    </select>
                </label>
                <div class="plan-switch" style="grid-template-columns:1fr 1fr 1fr">
                    <button class="btn btn-primary" name="result" value="approved">✓ Aprobar</button>
                    <button class="btn btn-outline" name="result" value="pending">⏳ Pendiente</button>
                    <button class="btn btn-danger" name="result" value="rejected">✕ Rechazar</button>
                </div>
            </form>
        </div>
    </div>
</main>
</body></html>
