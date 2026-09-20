<?php
require_once __DIR__ . '/_init.php';

if (is_post()) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        db_exec('DELETE FROM contact_requests WHERE id = ?', [(int) ($_POST['id'] ?? 0)]);
        flash('success', 'Solicitud eliminada.');
    }
    admin_back('contactos.php');
}

$rows = db_all('SELECT * FROM contact_requests ORDER BY created_at DESC LIMIT 500');

$active = 'contactos';
$pageTitle = 'Solicitudes de contacto';
include __DIR__ . '/../portal/_top.php';
?>
<div class="page-head"><div><h1>Solicitudes de contacto</h1><p class="muted">Mensajes enviados desde el formulario "Solicita una asesoría" de la página principal.</p></div></div>

<section class="panel">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Contacto</th><th>Institución</th><th>Mensaje</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="nowrap"><?= fecha($r['created_at'], true) ?></td>
                    <td><strong><?= e($r['name']) ?></strong><small class="block"><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></small><small class="block muted"><?= e($r['phone'] ?: '') ?></small></td>
                    <td><?= e($r['institution'] ?: '—') ?></td>
                    <td class="msg-cell"><?= nl2br(e($r['message'] ?: '—')) ?></td>
                    <td class="row-actions">
                        <form method="post" data-confirm="¿Eliminar esta solicitud?"><?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <button class="btn btn-danger btn-sm" name="action" value="delete">Eliminar</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="5" class="muted">No hay solicitudes todavía.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../portal/_bottom.php'; ?>
