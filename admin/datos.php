<?php
require_once __DIR__ . '/_init.php';

$KINDS = [
    'acceso'      => 'Conocer sus datos',
    'actualizar'  => 'Actualizar o corregir',
    'supresion'   => 'Eliminar sus datos',
    'revocatoria' => 'Revocar autorización',
    'copia'       => 'Copia de su información',
    'reclamo'     => 'Reclamo',
    'otro'        => 'Otra solicitud',
];
$STATUS = [
    'pendiente'  => ['Pendiente', 'warning'],
    'en_tramite' => ['En trámite', 'info'],
    'resuelta'   => ['Resuelta', 'success'],
];

if (is_post()) {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $row = db_one('SELECT * FROM data_requests WHERE id = ?', [$id]);

    if (!$row) {
        flash('error', 'Solicitud no encontrada.');
    } elseif ($action === 'estado') {
        $new = isset($STATUS[$_POST['status'] ?? '']) ? $_POST['status'] : 'pendiente';
        db_exec(
            'UPDATE data_requests SET status = ?, note = ?, answered_at = ? WHERE id = ?',
            [$new, mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 255) ?: null,
             $new === 'resuelta' ? date('Y-m-d H:i:s') : null, $id]
        );
        app_log('datos', 'Solicitud actualizada', ['id' => $id, 'estado' => $new, 'admin' => $user['id']]);
        flash('success', 'Solicitud marcada como ' . mb_strtolower($STATUS[$new][0]) . '.');
    } elseif ($action === 'delete') {
        db_exec('DELETE FROM data_requests WHERE id = ?', [$id]);
        flash('success', 'Solicitud eliminada.');
    }
    admin_back('datos.php');
}

$filter = $_GET['status'] ?? '';
$where = isset($STATUS[$filter]) ? ' WHERE status = ?' : '';
$rows = db_all('SELECT * FROM data_requests' . $where . ' ORDER BY (status = "pendiente") DESC, created_at DESC LIMIT 500',
    $where !== '' ? [$filter] : []);
$pendientes = (int) db_one("SELECT COUNT(*) c FROM data_requests WHERE status <> 'resuelta'")['c'];

$active = 'datos';
$pageTitle = 'Datos personales';
include __DIR__ . '/../portal/_top.php';
?>
<div class="page-head">
    <div>
        <h1>Solicitudes sobre datos personales</h1>
        <p class="muted">Ley 1581 de 2012: las consultas se responden en <strong>10 días hábiles</strong> y los reclamos en <strong>15 días hábiles</strong>.</p>
    </div>
    <?php if ($pendientes > 0): ?><span class="badge badge-warning"><?= $pendientes ?> sin resolver</span><?php endif; ?>
</div>

<form class="panel filters" method="get">
    <select name="status">
        <option value="">Todas</option>
        <?php foreach ($STATUS as $k => [$lbl]): ?>
            <option value="<?= $k ?>" <?= $filter === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm">Filtrar</button>
</form>

<section class="panel">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Solicitante</th><th>Solicitud</th><th>Detalle</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): [$lbl, $cls] = $STATUS[$r['status']] ?? [$r['status'], 'muted'];
                $dias = (int) floor((time() - strtotime($r['created_at'])) / 86400); ?>
                <tr>
                    <td class="nowrap"><?= fecha($r['created_at'], true) ?>
                        <?php if ($r['status'] !== 'resuelta' && $dias >= 8): ?>
                            <small class="block" style="color:var(--danger)">hace <?= $dias ?> días</small>
                        <?php endif; ?></td>
                    <td><strong><?= e($r['name']) ?></strong>
                        <small class="block"><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></small>
                        <small class="block muted"><?= e(trim($r['document'] . ' ' . $r['institution'])) ?></small></td>
                    <td><?= e($KINDS[$r['kind']] ?? $r['kind']) ?></td>
                    <td class="msg-cell"><?= nl2br(e($r['detail'] ?: '—')) ?>
                        <?php if ($r['note']): ?><small class="block muted">Nota: <?= e($r['note']) ?></small><?php endif; ?></td>
                    <td><span class="badge badge-<?= e($cls) ?>"><?= e($lbl) ?></span>
                        <?php if ($r['answered_at']): ?><small class="block muted"><?= fecha($r['answered_at']) ?></small><?php endif; ?></td>
                    <td class="row-actions">
                        <details class="edit-pop">
                            <summary class="btn btn-primary btn-sm">Atender</summary>
                            <form method="post" class="pop"><?= csrf_field() . admin_qs_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <input type="hidden" name="action" value="estado">
                                <label>Estado
                                    <select name="status">
                                        <?php foreach ($STATUS as $k => [$l]): ?>
                                            <option value="<?= $k ?>" <?= $r['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Nota interna<input name="note" maxlength="255" value="<?= e($r['note']) ?>"></label>
                                <button class="btn btn-primary btn-sm btn-block">Guardar</button>
                            </form>
                        </details>
                        <form method="post" data-confirm="¿Eliminar esta solicitud? Se pierde el registro de su atención."><?= csrf_field() . admin_qs_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <button class="btn btn-danger btn-sm" name="action" value="delete">Eliminar</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="6" class="muted">No hay solicitudes.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../portal/_bottom.php'; ?>
