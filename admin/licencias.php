<?php
require_once __DIR__ . '/_init.php';

if (is_post()) {
    csrf_check();
    $license = license_by_id((int) ($_POST['license_id'] ?? 0));
    $action = $_POST['action'] ?? '';
    if (!$license) {
        flash('error', 'Licencia no encontrada.');
    } elseif ($action === 'toggle') {
        $new = $license['status'] === 'active' ? 'suspended' : 'active';
        db_exec('UPDATE licenses SET status = ? WHERE id = ?', [$new, $license['id']]);
        flash('success', 'Licencia ' . $license['license_key'] . ($new === 'active' ? ' reactivada.' : ' suspendida.'));
    } elseif ($action === 'edit') {
        $seats = (int) ($_POST['seats'] ?? 0);
        $expires = (string) ($_POST['expires_at'] ?? '');
        $dt = DateTime::createFromFormat('Y-m-d', $expires);
        if ($seats < (int) $license['used_seats'] || $seats < 1 || $seats > 100000) {
            flash('error', 'Los cupos deben ser al menos ' . max(1, (int) $license['used_seats']) . ' (usuarios ya asignados).');
        } elseif (!$dt) {
            flash('error', 'Fecha de vencimiento inválida.');
        } else {
            db_exec('UPDATE licenses SET seats = ?, expires_at = ? WHERE id = ?', [$seats, $dt->format('Y-m-d 23:59:59'), $license['id']]);
            flash('success', 'Licencia ' . $license['license_key'] . ' actualizada.');
        }
    } elseif ($action === 'regen') {
        db_exec('UPDATE licenses SET license_key = ? WHERE id = ?', [generate_license_key(), $license['id']]);
        flash('success', 'Se generó un nuevo código para la licencia.');
    }
    admin_back('licencias.php');
}

$q = trim((string) ($_GET['q'] ?? ''));
$filter = $_GET['f'] ?? '';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(l.license_key LIKE ? OR u.institution LIKE ? OR u.email LIKE ? OR o.reference LIKE ?)';
    array_push($params, ...array_fill(0, 4, '%' . $q . '%'));
}
if ($filter === 'active')    $where[] = "l.status = 'active' AND l.expires_at > NOW()";
if ($filter === 'suspended') $where[] = "l.status = 'suspended'";
if ($filter === 'expired')   $where[] = 'l.expires_at <= NOW()';
if ($filter === 'expiring')  $where[] = "l.status = 'active' AND l.expires_at BETWEEN NOW() AND NOW() + INTERVAL 30 DAY";

$licenses = db_all(
    'SELECT l.*, o.reference, u.name AS owner_name, u.email AS owner_email, u.institution,
            (SELECT COUNT(*) FROM license_members m WHERE m.license_id = l.id) AS used_seats
       FROM licenses l JOIN orders o ON o.id = l.order_id JOIN users u ON u.id = l.user_id'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY l.created_at DESC LIMIT 500',
    $params
);

$active = 'licencias';
$pageTitle = 'Licencias';
include __DIR__ . '/../portal/_top.php';
?>
<div class="page-head"><div><h1>Licencias</h1><p class="muted">Todas las licencias emitidas. Puedes suspenderlas, ampliar cupos, extender la vigencia o gestionar sus usuarios.</p></div></div>

<form class="panel filters" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Código, institución, correo u orden">
    <select name="f">
        <?php foreach (['' => 'Todas', 'active' => 'Activas', 'expiring' => 'Vencen en 30 días', 'expired' => 'Vencidas', 'suspended' => 'Suspendidas'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= $filter === $k ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm">Filtrar</button>
</form>

<section class="panel">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Licencia</th><th>Cliente</th><th>Cupos</th><th>Vigencia</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($licenses as $l): [$lbl, $cls] = license_status_label($l); ?>
                <tr>
                    <td><strong><?= e($l['plan_name']) ?></strong><small class="block"><code class="code-sm"><?= e($l['license_key']) ?></code></small></td>
                    <td><?= e($l['institution']) ?><small class="block muted"><?= e($l['owner_email']) ?> · <?= e($l['reference']) ?></small></td>
                    <td class="nowrap"><?= (int) $l['used_seats'] ?> / <?= (int) $l['seats'] ?></td>
                    <td class="nowrap"><?= fecha($l['starts_at']) ?><small class="block muted">hasta <?= fecha($l['expires_at']) ?></small></td>
                    <td><span class="badge badge-<?= e($cls) ?>"><?= e($lbl) ?></span></td>
                    <td class="row-actions">
                        <a class="btn btn-outline btn-sm" href="<?= e(url('portal/licencia.php?id=' . $l['id'])) ?>">Usuarios</a>
                        <details class="edit-pop">
                            <summary class="btn btn-ghost btn-sm">Editar</summary>
                            <form method="post" class="pop"><?= csrf_field() . admin_qs_field() ?>
                                <input type="hidden" name="license_id" value="<?= (int) $l['id'] ?>">
                                <input type="hidden" name="action" value="edit">
                                <label>Cupos<input type="number" name="seats" min="<?= max(1, (int) $l['used_seats']) ?>" value="<?= (int) $l['seats'] ?>" required></label>
                                <label>Vence<input type="date" name="expires_at" value="<?= e(date('Y-m-d', strtotime($l['expires_at']))) ?>" required></label>
                                <button class="btn btn-primary btn-sm btn-block">Guardar</button>
                            </form>
                        </details>
                        <form method="post" data-confirm="¿Generar un nuevo código? El código actual dejará de ser válido."><?= csrf_field() . admin_qs_field() ?>
                            <input type="hidden" name="license_id" value="<?= (int) $l['id'] ?>">
                            <button class="btn btn-ghost btn-sm" name="action" value="regen">Nuevo código</button></form>
                        <form method="post" data-confirm="<?= $l['status'] === 'active' ? '¿Suspender esta licencia?' : '¿Reactivar esta licencia?' ?>"><?= csrf_field() . admin_qs_field() ?>
                            <input type="hidden" name="license_id" value="<?= (int) $l['id'] ?>">
                            <button class="btn <?= $l['status'] === 'active' ? 'btn-danger' : 'btn-primary' ?> btn-sm" name="action" value="toggle"><?= $l['status'] === 'active' ? 'Suspender' : 'Reactivar' ?></button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$licenses): ?><tr><td colspan="6" class="muted">No hay licencias con esos filtros.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../portal/_bottom.php'; ?>
