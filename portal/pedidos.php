<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$orders = db_all(
    'SELECT o.*, l.id AS license_id FROM orders o LEFT JOIN licenses l ON l.order_id = o.id
      WHERE o.user_id = ? ORDER BY o.created_at DESC',
    [$user['id']]
);
$statusMap = [
    'approved'     => ['Aprobado', 'success'],
    'pending'      => ['Pendiente', 'warning'],
    'review'       => ['En verificación', 'warning'],
    'rejected'     => ['Rechazado', 'danger'],
    'cancelled'    => ['Cancelado', 'muted'],
    'refunded'     => ['Reembolsado', 'muted'],
    'charged_back' => ['Contracargo', 'danger'],
];
$active = 'pedidos';
$pageTitle = 'Pedidos y pagos';
include __DIR__ . '/_top.php';
?>
<div class="page-head">
    <div>
        <h1>Pedidos y pagos</h1>
        <p class="muted">Historial de compras realizadas con Mercado Pago.</p>
    </div>
</div>

<section class="panel">
    <?php if (!$orders): ?>
        <div class="empty small-empty"><div class="empty-icon">🧾</div><p class="muted">No tienes pedidos todavía.</p>
            <a class="btn btn-primary" href="<?= e(url('')) ?>#precios">Ver planes</a></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Orden</th><th>Plan</th><th>Fecha</th><th>Valor</th><th>Estado</th><th>Pago MP</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($orders as $o): [$lbl, $cls] = $statusMap[$o['status']] ?? [$o['status'], 'muted']; ?>
                    <tr>
                        <td><strong><?= e($o['reference']) ?></strong></td>
                        <td><?= e($o['plan_name']) ?><small class="block muted"><?= (int) $o['seats'] ?> usuarios</small></td>
                        <td class="nowrap"><?= fecha($o['created_at'], true) ?></td>
                        <td class="nowrap"><?= e(money($o['amount'])) ?></td>
                        <td><span class="badge badge-<?= e($cls) ?>"><?= e($lbl) ?></span></td>
                        <td><?= e($o['mp_payment_id'] ?: '—') ?><?php if ($o['payment_method']): ?><small class="block muted"><?= e(strtoupper($o['payment_method'])) ?></small><?php endif; ?></td>
                        <td class="row-actions">
                            <?php if ($o['license_id']): ?>
                                <a class="btn btn-outline btn-sm" href="<?= e(url('portal/licencia.php?id=' . $o['license_id'])) ?>">Ver licencia</a>
                            <?php elseif (in_array($o['status'], ['rejected', 'cancelled', 'pending'], true)): ?>
                                <?php if ($o['status'] === 'pending' && $o['payment_url']): ?>
                                    <a class="btn btn-outline btn-sm" href="<?= e(url('pago/resultado.php?ref=' . urlencode($o['reference']))) ?>"><?= $o['payment_method'] === 'efecty' ? 'Ver cupón' : 'Ver estado' ?></a>
                                <?php endif; ?>
                                <?php if ($o['status'] === 'cancelled'): ?>
                                    <a class="btn btn-primary btn-sm" href="<?= e(url('checkout.php?plan=' . $o['plan_code'])) ?>">Comprar de nuevo</a>
                                <?php else: ?>
                                    <a class="btn btn-primary btn-sm" href="<?= e(url('pago/pagar.php?ref=' . urlencode($o['reference']))) ?>">Pagar</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/_bottom.php'; ?>
