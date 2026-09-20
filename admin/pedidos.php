<?php
require_once __DIR__ . '/_init.php';

if (is_post()) {
    csrf_check();
    $order = db_one('SELECT * FROM orders WHERE id = ?', [(int) ($_POST['order_id'] ?? 0)]);
    $action = $_POST['action'] ?? '';
    if (!$order) {
        flash('error', 'Pedido no encontrado.');
    } elseif ($action === 'approve') {
        if ($order['status'] === 'approved') {
            flash('info', 'El pedido ya estaba aprobado.');
        } else {
            approve_order_manually($order, trim((string) ($_POST['note'] ?? '')) ?: 'sin nota');
            flash('success', 'Pedido ' . $order['reference'] . ' aprobado y licencia creada.');
        }
    } elseif ($action === 'sync') {
        if (!mp_enabled()) {
            flash('error', 'Mercado Pago no está configurado.');
        } else {
            $updated = sync_order_with_mp($order);
            flash($updated ? 'success' : 'info', $updated
                ? 'Sincronizado con Mercado Pago. Estado actual: ' . ($ORDER_STATUS[$updated['status']][0] ?? $updated['status']) . '.'
                : 'Mercado Pago no registra pagos para esta orden.');
        }
    } elseif ($action === 'cancel') {
        if ($order['status'] === 'approved') {
            flash('error', 'Un pedido aprobado no se puede cancelar; suspende la licencia en su lugar.');
        } else {
            db_exec("UPDATE orders SET status = 'cancelled', status_detail = 'Cancelado por administrador', updated_at = NOW() WHERE id = ?", [$order['id']]);
            flash('success', 'Pedido cancelado.');
        }
    } elseif ($action === 'delete') {
        if ($order['status'] === 'approved') {
            flash('error', 'No se puede eliminar un pedido aprobado.');
        } else {
            db_exec('DELETE FROM orders WHERE id = ?', [$order['id']]);
            flash('success', 'Pedido eliminado.');
        }
    }
    admin_back('pedidos.php');
}

$status = $_GET['status'] ?? '';
$q = trim((string) ($_GET['q'] ?? ''));
$where = [];
$params = [];
if (isset($ORDER_STATUS[$status])) {
    $where[] = 'o.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $where[] = '(o.reference LIKE ? OR u.email LIKE ? OR u.name LIKE ? OR u.institution LIKE ? OR o.mp_payment_id LIKE ?)';
    array_push($params, ...array_fill(0, 5, '%' . $q . '%'));
}
$orders = db_all(
    'SELECT o.*, u.name AS user_name, u.email AS user_email, u.institution, l.id AS license_id
       FROM orders o JOIN users u ON u.id = o.user_id LEFT JOIN licenses l ON l.order_id = o.id'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY o.created_at DESC LIMIT 500',
    $params
);

$active = 'pedidos';
$pageTitle = 'Pedidos';
include __DIR__ . '/../portal/_top.php';
?>
<div class="page-head"><div><h1>Pedidos y pagos</h1><p class="muted">Todas las órdenes del sitio.</p></div></div>

<form class="panel filters" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Referencia, cliente, correo o ID de pago">
    <select name="status">
        <option value="">Todos los estados</option>
        <?php foreach ($ORDER_STATUS as $k => [$lbl]): ?><option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm">Filtrar</button>
</form>

<section class="panel">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Orden</th><th>Cliente</th><th>Plan</th><th>Valor</th><th>Estado</th><th>Pago MP</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): [$lbl, $cls] = $ORDER_STATUS[$o['status']] ?? [$o['status'], 'muted']; ?>
                <tr>
                    <td><strong><?= e($o['reference']) ?></strong><small class="block muted"><?= fecha($o['created_at'], true) ?></small></td>
                    <td><?= e($o['institution']) ?><small class="block muted"><?= e($o['user_name']) ?> · <?= e($o['user_email']) ?></small></td>
                    <td><?= e($o['plan_name']) ?><small class="block muted"><?= (int) $o['seats'] ?> usuarios</small></td>
                    <td class="nowrap"><?= e(money_short($o['amount'])) ?></td>
                    <td><span class="badge badge-<?= e($cls) ?>"><?= e($lbl) ?></span><?php if ($o['status_detail']): ?><small class="block muted"><?= e($o['status_detail']) ?></small><?php endif; ?></td>
                    <td><?= e($o['mp_payment_id'] ?: '—') ?><?php if ($o['payment_method']): ?><small class="block muted"><?= e(strtoupper($o['payment_method'])) ?></small><?php endif; ?></td>
                    <td class="row-actions">
                        <?php if ($o['license_id']): ?>
                            <a class="btn btn-outline btn-sm" href="<?= e(url('portal/licencia.php?id=' . $o['license_id'])) ?>">Licencia</a>
                        <?php endif; ?>
                        <?php if ($o['status'] !== 'approved'): ?>
                            <form method="post"><?= csrf_field() . admin_qs_field() ?><input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                <button class="btn btn-ghost btn-sm" name="action" value="sync" title="Consultar estado en Mercado Pago">Sincronizar</button></form>
                            <details class="edit-pop">
                                <summary class="btn btn-primary btn-sm">Aprobar</summary>
                                <form method="post" class="pop"><?= csrf_field() . admin_qs_field() ?>
                                    <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <p class="small">Aprobar manualmente crea la licencia sin pago en Mercado Pago.</p>
                                    <label>Nota (ej. transferencia #)<input name="note" maxlength="80"></label>
                                    <button class="btn btn-primary btn-sm btn-block">Confirmar aprobación</button>
                                </form>
                            </details>
                            <?php if ($o['status'] !== 'cancelled'): ?>
                                <form method="post" data-confirm="¿Cancelar el pedido <?= e($o['reference']) ?>?"><?= csrf_field() . admin_qs_field() ?><input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                    <button class="btn btn-ghost btn-sm" name="action" value="cancel">Cancelar</button></form>
                            <?php endif; ?>
                            <form method="post" data-confirm="¿Eliminar definitivamente el pedido <?= e($o['reference']) ?>?"><?= csrf_field() . admin_qs_field() ?><input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                <button class="btn btn-danger btn-sm" name="action" value="delete">Eliminar</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?><tr><td colspan="7" class="muted">No hay pedidos con esos filtros.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../portal/_bottom.php'; ?>
