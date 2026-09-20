<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $institution = trim((string) ($_POST['institution'] ?? ''));
        if ($name === '' || $institution === '') {
            flash('error', 'El nombre y la institución son obligatorios.');
        } else {
            db_exec('UPDATE users SET name = ?, institution = ?, phone = ?, city = ? WHERE id = ?', [
                mb_substr($name, 0, 120), mb_substr($institution, 0, 190),
                mb_substr(trim((string) ($_POST['phone'] ?? '')), 0, 30),
                mb_substr(trim((string) ($_POST['city'] ?? '')), 0, 80), $user['id'],
            ]);
            flash('success', 'Datos actualizados.');
        }
        redirect('portal/cuenta.php');
    }

    if ($action === 'password') {
        $current = (string) ($_POST['current'] ?? '');
        $new = (string) ($_POST['new'] ?? '');
        if (!password_verify($current, $user['password_hash'])) {
            flash('error', 'La contraseña actual no es correcta.');
        } elseif (strlen($new) < 8) {
            flash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
        } elseif ($new !== ($_POST['confirm'] ?? '')) {
            flash('error', 'Las contraseñas nuevas no coinciden.');
        } else {
            db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            session_regenerate_id(true);
            flash('success', 'Contraseña actualizada.');
        }
        redirect('portal/cuenta.php');
    }
}

$active = 'cuenta';
$pageTitle = 'Mi cuenta';
include __DIR__ . '/_top.php';
?>
<div class="page-head"><div><h1>Mi cuenta</h1><p class="muted">Datos del administrador y de la institución.</p></div></div>

<div class="two-col even">
    <form class="panel" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="profile">
        <h2>Datos generales</h2>
        <label>Nombre del administrador<input name="name" value="<?= e($user['name']) ?>" required maxlength="120"></label>
        <label>Correo electrónico<input value="<?= e($user['email']) ?>" disabled></label>
        <label>Institución<input name="institution" value="<?= e($user['institution']) ?>" required maxlength="190"></label>
        <div class="form-row">
            <label>Documento<input value="<?= e($user['document_type'] . ' ' . $user['document_number']) ?>" disabled></label>
            <label>Teléfono<input name="phone" value="<?= e($user['phone']) ?>" maxlength="30"></label>
        </div>
        <label>Ciudad<input name="city" value="<?= e($user['city']) ?>" maxlength="80"></label>
        <button class="btn btn-primary">Guardar cambios</button>
    </form>

    <form class="panel" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="password">
        <h2>Cambiar contraseña</h2>
        <label>Contraseña actual<input type="password" name="current" required autocomplete="current-password"></label>
        <label>Nueva contraseña<input type="password" name="new" required minlength="8" autocomplete="new-password"></label>
        <label>Confirmar nueva contraseña<input type="password" name="confirm" required minlength="8" autocomplete="new-password"></label>
        <button class="btn btn-primary">Actualizar contraseña</button>
        <p class="muted small mt-24">Cliente desde <?= fecha($user['created_at']) ?>. Para cambiar el correo o el documento de identificación escribe a <?= e(SUPPORT_EMAIL) ?>.</p>
    </form>
</div>
<?php include __DIR__ . '/_bottom.php'; ?>
