<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$licenseId = (int) ($_GET['id'] ?? $_POST['license_id'] ?? 0);
// El administrador puede gestionar cualquier licencia
$license = is_admin($user) ? license_by_id($licenseId) : user_license((int) $user['id'], $licenseId);
if (!$license) {
    flash('error', 'La licencia no existe o no pertenece a tu cuenta.');
    redirect('portal/licencias.php');
}
$self = 'portal/licencia.php?id=' . $license['id'];

// ---------------------------------------------------------------------------
// Acciones
// ---------------------------------------------------------------------------
if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $err = add_license_member($license, (string) ($_POST['name'] ?? ''), (string) ($_POST['email'] ?? ''), (string) ($_POST['role'] ?? 'docente'), (string) ($_POST['area'] ?? ''));
        if ($err) {
            set_old($_POST);
            flash('error', $err);
        } else {
            clear_old();
            flash('success', 'Usuario agregado a la licencia.');
        }
        redirect($self);
    }

    if ($action === 'bulk') {
        $lines = [];
        $raw = (string) ($_POST['bulk'] ?? '');
        if (!empty($_FILES['csv']['tmp_name']) && is_uploaded_file($_FILES['csv']['tmp_name'])) {
            if ($_FILES['csv']['size'] > 1024 * 1024) {
                flash('error', 'El archivo supera 1 MB.');
                redirect($self);
            }
            $raw .= "\n" . file_get_contents($_FILES['csv']['tmp_name']);
        }
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            if (trim($line) === '') continue;
            $cols = array_map('trim', str_getcsv($line, str_contains($line, ';') ? ';' : ','));
            // Omitir encabezado
            if (isset($cols[1]) && in_array(mb_strtolower($cols[1]), ['correo', 'email', 'e-mail'], true)) continue;
            $lines[] = $cols;
        }
        if (!$lines) {
            flash('error', 'No encontramos usuarios para agregar. Usa el formato: Nombre, correo, rol.');
            redirect($self);
        }
        $roleByLabel = array_change_key_case(array_flip(array_map('mb_strtolower', $MEMBER_ROLES)));
        $ok = 0;
        $fails = [];
        foreach (array_slice($lines, 0, 500) as $cols) {
            $roleIn = mb_strtolower($cols[2] ?? 'docente');
            $role = isset($MEMBER_ROLES[$roleIn]) ? $roleIn : ($roleByLabel[$roleIn] ?? 'docente');
            $err = add_license_member($license, $cols[0] ?? '', $cols[1] ?? '', $role, $cols[3] ?? null);
            if ($err) {
                $fails[] = ($cols[1] ?? $cols[0] ?? '?') . ': ' . $err;
                if (str_starts_with($err, 'No quedan cupos')) break;
            } else {
                $ok++;
            }
        }
        if ($ok) flash('success', "Se agregaron $ok usuario(s) a la licencia.");
        if ($fails) flash('warning', count($fails) . ' fila(s) no se agregaron: ' . implode(' · ', array_slice($fails, 0, 5)) . (count($fails) > 5 ? '…' : ''));
        redirect($self);
    }

    if ($action === 'update') {
        $memberId = (int) ($_POST['member_id'] ?? 0);
        $role = isset($MEMBER_ROLES[$_POST['role'] ?? '']) ? $_POST['role'] : 'docente';
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            flash('error', 'El nombre no puede quedar vacío.');
        } else {
            db_exec('UPDATE license_members SET name = ?, role = ?, area = ? WHERE id = ? AND license_id = ?',
                [$name, $role, trim((string) ($_POST['area'] ?? '')) ?: null, $memberId, $license['id']]);
            flash('success', 'Usuario actualizado.');
        }
        redirect($self);
    }

    if ($action === 'remove') {
        $n = db_exec('DELETE FROM license_members WHERE id = ? AND license_id = ?', [(int) ($_POST['member_id'] ?? 0), $license['id']]);
        flash($n ? 'success' : 'error', $n ? 'Usuario retirado. El cupo quedó disponible.' : 'No se encontró el usuario.');
        redirect($self);
    }
}

// Exportar CSV
if (($_GET['export'] ?? '') === 'csv') {
    $rows = license_members((int) $license['id']);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="usuarios-' . $license['license_key'] . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Nombre', 'Correo', 'Rol', 'Área / sede', 'Código de acceso', 'Fecha de alta'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [$r['name'], $r['email'], $MEMBER_ROLES[$r['role']] ?? $r['role'], $r['area'], $r['access_code'], $r['created_at']], ';');
    }
    fclose($out);
    exit;
}

$members = license_members((int) $license['id']);
$used = count($members);
$free = max(0, (int) $license['seats'] - $used);
$pct = $license['seats'] > 0 ? min(100, round($used / $license['seats'] * 100)) : 0;
$usable = license_is_usable($license);
[$stLabel, $stClass] = license_status_label($license);
$daysLeft = max(0, (int) floor((strtotime($license['expires_at']) - time()) / 86400));
$plan = plan($license['plan_code']);

$active = 'licencias';
$pageTitle = $license['plan_name'];
include __DIR__ . '/_top.php';
?>
<a class="page-back" href="<?= e(url('portal/licencias.php')) ?>">← Mis licencias</a>

<section class="license-hero <?= $license['plan_code'] === 'volumen' ? 'lc-volumen' : 'lc-escuela' ?>">
    <div>
        <span class="badge badge-<?= e($stClass) ?>"><?= e($stLabel) ?></span>
        <h1><?= e($license['plan_name']) ?></h1>
        <p><?= e($plan['tagline'] ?? '') ?></p>
        <div class="lh-key">
            <small>Código de licencia</small>
            <code><?= e($license['license_key']) ?></code>
            <button type="button" class="copy-btn light" data-copy="<?= e($license['license_key']) ?>">Copiar</button>
        </div>
    </div>
    <div class="lh-seats">
        <div class="ring" style="--p: <?= $pct ?>"><div><strong><?= $used ?></strong><small>de <?= (int) $license['seats'] ?></small></div></div>
        <span><?= $free ?> cupos disponibles</span>
    </div>
</section>

<div class="kpis">
    <div class="kpi"><span class="kpi-icon c-purple">📅</span><div><small>Fecha de inicio</small><strong class="kpi-sm"><?= fecha($license['starts_at']) ?></strong></div></div>
    <div class="kpi"><span class="kpi-icon c-pink">⏳</span><div><small>Vence</small><strong class="kpi-sm"><?= fecha($license['expires_at']) ?></strong><small><?= $daysLeft ?> días restantes</small></div></div>
    <div class="kpi"><span class="kpi-icon c-green">💳</span><div><small>Pago</small><strong class="kpi-sm"><?= e(money($license['amount'])) ?></strong><small>Orden <?= e($license['reference']) ?></small></div></div>
    <div class="kpi"><span class="kpi-icon c-orange">🧾</span><div><small>ID de pago Mercado Pago</small><strong class="kpi-sm"><?= e($license['mp_payment_id'] ?: '—') ?></strong><small><?= e(strtoupper((string) $license['payment_method'])) ?></small></div></div>
</div>

<div class="two-col">
    <section class="panel">
        <h2>Agregar usuarios</h2>
        <?php if (!$usable): ?>
            <div class="alert alert-warning">Esta licencia no está activa; no es posible agregar usuarios.</div>
        <?php elseif ($free === 0): ?>
            <div class="alert alert-warning">Ya usaste todos los cupos. Retira un usuario o <a href="<?= e(url('checkout.php')) ?>">adquiere otra licencia</a>.</div>
        <?php else: ?>
            <div data-tabs>
                <div class="seg">
                    <button type="button" class="active" data-tab="one">Individual</button>
                    <button type="button" data-tab="many">Carga masiva</button>
                </div>
                <form method="post" data-panel="one">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="license_id" value="<?= (int) $license['id'] ?>">
                    <label>Nombre completo<input name="name" required maxlength="120" value="<?= old('name') ?>"></label>
                    <label>Correo electrónico<input type="email" name="email" required maxlength="190" value="<?= old('email') ?>"></label>
                    <div class="form-row">
                        <label>Rol
                            <select name="role">
                                <?php foreach ($MEMBER_ROLES as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?>
                            </select>
                        </label>
                        <label>Área o sede <span class="field-hint">(opcional)</span><input name="area" maxlength="120" value="<?= old('area') ?>" placeholder="Matemáticas / Sede norte"></label>
                    </div>
                    <button class="btn btn-primary btn-block">Agregar usuario</button>
                </form>
                <form method="post" enctype="multipart/form-data" data-panel="many" hidden>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="bulk">
                    <input type="hidden" name="license_id" value="<?= (int) $license['id'] ?>">
                    <label>Pega un usuario por línea: <span class="field-hint">Nombre, correo, rol, área</span>
                        <textarea name="bulk" rows="7" placeholder="Ana Gómez, ana@colegio.edu.co, docente, Ciencias&#10;Luis Pérez, luis@colegio.edu.co, coordinador"></textarea>
                    </label>
                    <label>O sube un archivo CSV <span class="field-hint">(mismas columnas, separado por coma o punto y coma)</span>
                        <input type="file" name="csv" accept=".csv,text/csv">
                    </label>
                    <p class="muted small">Roles válidos: <?= e(implode(', ', array_keys($MEMBER_ROLES))) ?>. Quedan <?= $free ?> cupos.</p>
                    <button class="btn btn-primary btn-block">Importar usuarios</button>
                </form>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel panel-wide">
        <div class="panel-head">
            <h2>Usuarios de la licencia <span class="count"><?= $used ?>/<?= (int) $license['seats'] ?></span></h2>
            <div class="panel-actions">
                <input type="search" id="member-search" placeholder="Buscar…" aria-label="Buscar usuarios">
                <?php if ($members): ?><a class="btn btn-outline btn-sm" href="<?= e(url($self . '&export=csv')) ?>">Exportar CSV</a><?php endif; ?>
            </div>
        </div>
        <div class="progress big"><span style="width: <?= $pct ?>%"></span></div>

        <?php if (!$members): ?>
            <div class="empty small-empty">
                <div class="empty-icon">👩‍🏫</div>
                <p class="muted">Todavía no has agregado usuarios. Agrega a tus docentes para que empiecen a usar <?= e(BRAND_SHORT) ?>.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Usuario</th><th>Rol</th><th>Área / sede</th><th>Código</th><th>Alta</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($members as $m): ?>
                        <tr data-member="<?= e(mb_strtolower($m['name'] . ' ' . $m['email'] . ' ' . $m['area'])) ?>">
                            <td><div class="member"><span class="avatar sm"><?= e(mb_strtoupper(mb_substr($m['name'], 0, 1))) ?></span><div><strong><?= e($m['name']) ?></strong><small><?= e($m['email']) ?></small></div></div></td>
                            <td><span class="role role-<?= e($m['role']) ?>"><?= e($MEMBER_ROLES[$m['role']] ?? $m['role']) ?></span></td>
                            <td><?= e($m['area'] ?: '—') ?></td>
                            <td><code class="code-sm"><?= e($m['access_code']) ?></code></td>
                            <td class="nowrap"><?= fecha($m['created_at']) ?></td>
                            <td class="row-actions">
                                <details class="edit-pop">
                                    <summary class="btn btn-ghost btn-sm">Editar</summary>
                                    <form method="post" class="pop">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="license_id" value="<?= (int) $license['id'] ?>">
                                        <input type="hidden" name="member_id" value="<?= (int) $m['id'] ?>">
                                        <label>Nombre<input name="name" value="<?= e($m['name']) ?>" required maxlength="120"></label>
                                        <label>Rol<select name="role"><?php foreach ($MEMBER_ROLES as $k => $v): ?><option value="<?= e($k) ?>" <?= $m['role'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></label>
                                        <label>Área / sede<input name="area" value="<?= e($m['area']) ?>" maxlength="120"></label>
                                        <button class="btn btn-primary btn-sm btn-block">Guardar</button>
                                    </form>
                                </details>
                                <form method="post" data-confirm="¿Retirar a <?= e($m['name']) ?> de la licencia?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="license_id" value="<?= (int) $license['id'] ?>">
                                    <input type="hidden" name="member_id" value="<?= (int) $m['id'] ?>">
                                    <button class="btn btn-danger btn-sm">Retirar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php clear_old(); include __DIR__ . '/_bottom.php'; ?>
