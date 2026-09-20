<?php
require_once __DIR__ . '/_init.php';

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $name = trim((string) ($_POST['name'] ?? ''));
        $institution = trim((string) ($_POST['institution'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($name === '' || $institution === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Nombre, institución y un correo válido son obligatorios.');
        } elseif (strlen($password) < 8) {
            flash('error', 'La contraseña debe tener al menos 8 caracteres.');
        } elseif (db_one('SELECT id FROM users WHERE email = ?', [$email])) {
            flash('error', 'Ya existe una cuenta con ese correo.');
        } else {
            db_exec(
                'INSERT INTO users (name, email, password_hash, role, institution, document_type, document_number, phone, city)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    mb_substr($name, 0, 120), $email, password_hash($password, PASSWORD_DEFAULT),
                    ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'customer',
                    mb_substr($institution, 0, 190),
                    in_array($_POST['document_type'] ?? '', ['NIT', 'CC', 'CE'], true) ? $_POST['document_type'] : 'NIT',
                    mb_substr(trim((string) ($_POST['document_number'] ?? '')) ?: '-', 0, 30),
                    mb_substr(trim((string) ($_POST['phone'] ?? '')), 0, 30),
                    mb_substr(trim((string) ($_POST['city'] ?? '')), 0, 80),
                ]
            );
            flash('success', 'Cuenta creada para ' . $email . '.');
        }
        admin_back('clientes.php');
    }

    $target = db_one('SELECT * FROM users WHERE id = ?', [(int) ($_POST['user_id'] ?? 0)]);
    if (!$target) {
        flash('error', 'Cliente no encontrado.');
        admin_back('clientes.php');
    }
    $isSelf = (int) $target['id'] === (int) $user['id'];

    if ($action === 'password') {
        $new = (string) ($_POST['password'] ?? '');
        if (strlen($new) < 8) {
            flash('error', 'La contraseña debe tener al menos 8 caracteres.');
        } else {
            db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $target['id']]);
            flash('success', 'Contraseña actualizada para ' . $target['email'] . '.');
        }
    } elseif ($action === 'role') {
        if ($isSelf) {
            flash('error', 'No puedes quitarte tu propio rol de administrador.');
        } else {
            $new = $target['role'] === 'admin' ? 'customer' : 'admin';
            db_exec('UPDATE users SET role = ? WHERE id = ?', [$new, $target['id']]);
            flash('success', $target['email'] . ($new === 'admin' ? ' ahora es administrador.' : ' ya no es administrador.'));
        }
    } elseif ($action === 'license') {
        $plan = plan((string) ($_POST['plan'] ?? ''));
        if (!$plan) {
            flash('error', 'Plan inválido.');
        } else {
            $order = create_order((int) $target['id'], $plan);
            approve_order_manually($order, trim((string) ($_POST['note'] ?? '')) ?: 'asignada desde administración');
            flash('success', $plan['name'] . ' asignada a ' . $target['institution'] . ' (orden ' . $order['reference'] . ').');
        }
    } elseif ($action === 'delete') {
        if ($isSelf) {
            flash('error', 'No puedes eliminar tu propia cuenta.');
        } elseif (strtolower($target['email']) === strtolower(ADMIN_EMAIL)) {
            flash('error', 'La cuenta principal de administración no se puede eliminar.');
        } else {
            db_exec('DELETE FROM users WHERE id = ?', [$target['id']]);
            flash('success', 'Cuenta ' . $target['email'] . ' eliminada junto con sus pedidos y licencias.');
        }
    }
    admin_back('clientes.php');
}

$q = trim((string) ($_GET['q'] ?? ''));
$params = [];
$where = '';
if ($q !== '') {
    $where = ' WHERE u.name LIKE ? OR u.email LIKE ? OR u.institution LIKE ? OR u.document_number LIKE ?';
    $params = array_fill(0, 4, '%' . $q . '%');
}
$users = db_all(
    "SELECT u.*,
            (SELECT COUNT(*) FROM licenses l WHERE l.user_id = u.id AND l.status = 'active' AND l.expires_at > NOW()) AS active_licenses,
            (SELECT COALESCE(SUM(amount),0) FROM orders o WHERE o.user_id = u.id AND o.status = 'approved') AS total_paid
       FROM users u" . $where . ' ORDER BY u.created_at DESC LIMIT 500',
    $params
);

$active = 'clientes';
$pageTitle = 'Clientes';
include __DIR__ . '/../portal/_top.php';
?>
<div class="page-head">
    <div><h1>Clientes</h1><p class="muted">Cuentas del portal, licencias y permisos.</p></div>
    <details class="edit-pop">
        <summary class="btn btn-primary">+ Nueva cuenta</summary>
        <form method="post" class="pop pop-wide"><?= csrf_field() . admin_qs_field() ?>
            <input type="hidden" name="action" value="create">
            <label>Institución<input name="institution" required maxlength="190"></label>
            <label>Nombre del responsable<input name="name" required maxlength="120"></label>
            <label>Correo<input type="email" name="email" required maxlength="190"></label>
            <div class="form-row">
                <label>Documento<select name="document_type"><option>NIT</option><option>CC</option><option>CE</option></select></label>
                <label>Número<input name="document_number" maxlength="30"></label>
            </div>
            <div class="form-row">
                <label>Teléfono<input name="phone" maxlength="30"></label>
                <label>Ciudad<input name="city" maxlength="80"></label>
            </div>
            <label>Contraseña inicial<input type="text" name="password" required minlength="8"></label>
            <label>Rol<select name="role"><option value="customer">Cliente</option><option value="admin">Administrador</option></select></label>
            <button class="btn btn-primary btn-sm btn-block">Crear cuenta</button>
        </form>
    </details>
</div>

<form class="panel filters" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nombre, correo, institución o documento">
    <button class="btn btn-primary btn-sm">Buscar</button>
</form>

<section class="panel">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Cliente</th><th>Documento</th><th>Contacto</th><th>Licencias</th><th>Pagado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><div class="member"><span class="avatar sm"><?= e(mb_strtoupper(mb_substr($u['institution'], 0, 1))) ?></span><div>
                        <strong><?= e($u['institution']) ?></strong>
                        <?php if ($u['role'] === 'admin'): ?><span class="role role-administrador">Admin</span><?php endif; ?>
                        <small><?= e($u['name']) ?> · <?= e($u['email']) ?></small></div></div></td>
                    <td class="nowrap"><?= e($u['document_type'] . ' ' . $u['document_number']) ?></td>
                    <td><?= e($u['phone'] ?: '—') ?><small class="block muted"><?= e($u['city'] ?: '') ?></small></td>
                    <td><?= (int) $u['active_licenses'] ?> activas<small class="block muted">desde <?= fecha($u['created_at']) ?></small></td>
                    <td class="nowrap"><?= e(money_short($u['total_paid'])) ?></td>
                    <td class="row-actions">
                        <a class="btn btn-outline btn-sm" href="<?= e(url('admin/licencias.php?q=' . urlencode($u['email']))) ?>">Licencias</a>
                        <details class="edit-pop">
                            <summary class="btn btn-primary btn-sm">Asignar licencia</summary>
                            <form method="post" class="pop"><?= csrf_field() . admin_qs_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                <input type="hidden" name="action" value="license">
                                <p class="small">Crea una orden aprobada y su licencia, sin cobro en Mercado Pago.</p>
                                <label>Plan<select name="plan"><?php foreach ($PLANS as $p): ?><option value="<?= e($p['code']) ?>"><?= e($p['name']) ?> (<?= money_short($p['price']) ?>)</option><?php endforeach; ?></select></label>
                                <label>Nota<input name="note" maxlength="80" placeholder="Transferencia, cortesía…"></label>
                                <button class="btn btn-primary btn-sm btn-block">Asignar</button>
                            </form>
                        </details>
                        <details class="edit-pop">
                            <summary class="btn btn-ghost btn-sm">Más</summary>
                            <div class="pop">
                                <form method="post"><?= csrf_field() . admin_qs_field() ?>
                                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                    <input type="hidden" name="action" value="password">
                                    <label>Nueva contraseña<input type="text" name="password" minlength="8" required></label>
                                    <button class="btn btn-outline btn-sm btn-block">Cambiar contraseña</button>
                                </form>
                                <?php if ((int) $u['id'] !== (int) $user['id']): ?>
                                    <form method="post" class="mt-12" data-confirm="¿Cambiar el rol de <?= e($u['email']) ?>?"><?= csrf_field() . admin_qs_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                        <button class="btn btn-ghost btn-sm btn-block" name="action" value="role"><?= $u['role'] === 'admin' ? 'Quitar administrador' : 'Hacer administrador' ?></button>
                                    </form>
                                    <?php if (strtolower($u['email']) !== strtolower(ADMIN_EMAIL)): ?>
                                    <form method="post" class="mt-12" data-confirm="¿Eliminar la cuenta <?= e($u['email']) ?> con TODOS sus pedidos, licencias y usuarios? No se puede deshacer."><?= csrf_field() . admin_qs_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                        <button class="btn btn-danger btn-sm btn-block" name="action" value="delete">Eliminar cuenta</button>
                                    </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?><tr><td colspan="6" class="muted">Sin resultados.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../portal/_bottom.php'; ?>
