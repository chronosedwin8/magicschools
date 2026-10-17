<?php
/**
 * Biblioteca: todo lo que el docente ha creado.
 * El cliente institucional ve, además, lo de toda su licencia.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

[$user, $license] = require_tools_access();
$verTodo = !is_teacher($user);

// Borrar un documento propio
if (is_post()) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        $gen = ai_generation((int) ($_POST['id'] ?? 0), $user);
        if ($gen && ((int) $gen['user_id'] === (int) $user['id'] || $verTodo)) {
            db_exec('DELETE FROM ai_generations WHERE id = ?', [$gen['id']]);
            app_log('ai', 'Documento eliminado', ['id' => $gen['id'], 'usuario' => $user['id']]);
            flash('success', 'Documento eliminado.');
        } else {
            flash('error', 'No encontramos ese documento.');
        }
    }
    redirect('portal/documentos.php');
}

$q = trim((string) ($_GET['q'] ?? ''));
$herramienta = (string) ($_GET['h'] ?? '');

$where = $verTodo ? ['g.license_id = ?'] : ['g.user_id = ?'];
$params = [$verTodo ? $license['id'] : $user['id']];
if ($q !== '') {
    $where[] = '(g.title LIKE ? OR g.tool_name LIKE ?)';
    array_push($params, '%' . $q . '%', '%' . $q . '%');
}
if (ai_tool($herramienta)) {
    $where[] = 'g.tool_slug = ?';
    $params[] = $herramienta;
}

$docs = db_all(
    'SELECT g.* FROM ai_generations g WHERE ' . implode(' AND ', $where) . ' ORDER BY g.created_at DESC LIMIT 200',
    $params
);
$total = count($docs);

$active = 'documentos';
$pageTitle = 'Mis documentos';
$pageCss = 'assets/css/tools.css';
include __DIR__ . '/_top.php';
?>
<div class="page-head">
    <div>
        <h1><?= $verTodo ? 'Documentos de la institución' : 'Mis documentos' ?></h1>
        <p class="muted">Todo lo que <?= $verTodo ? 'ha creado tu equipo' : 'has creado' ?> queda aquí para consultarlo y descargarlo.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('portal/herramientas.php')) ?>">+ Crear documento</a>
</div>

<form class="panel filters" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por título">
    <select name="h">
        <option value="">Todas las herramientas</option>
        <?php foreach (ai_tools_visible() as $t): ?>
            <option value="<?= e($t['slug']) ?>" <?= $herramienta === $t['slug'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm">Filtrar</button>
</form>

<section class="panel">
    <?php if (!$docs): ?>
        <div class="empty small-empty">
            <div class="empty-icon"><?= icon('documento', 34) ?></div>
            <p class="muted"><?= $q !== '' || $herramienta !== '' ? 'No hay documentos con ese filtro.' : 'Todavía no hay documentos. Crea el primero desde las herramientas.' ?></p>
            <a class="btn btn-primary" href="<?= e(url('portal/herramientas.php')) ?>">Ir a las herramientas</a>
        </div>
    <?php else: ?>
        <p class="muted small"><?= $total ?> documento<?= $total === 1 ? '' : 's' ?></p>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Documento</th><th>Herramienta</th><?php if ($verTodo): ?><th>Docente</th><?php endif; ?><th>Fecha</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($docs as $d): ?>
                    <tr>
                        <td><strong><?= e($d['title'] ?: $d['tool_name']) ?></strong></td>
                        <td><?= e($d['tool_name']) ?></td>
                        <?php if ($verTodo): ?><td><?= e($d['author_name']) ?><small class="block muted"><?= e($d['author_email']) ?></small></td><?php endif; ?>
                        <td class="nowrap"><?= fecha($d['created_at'], true) ?></td>
                        <td>
                            <?php if ($d['status'] === 'done'): ?><span class="badge badge-success">Listo</span>
                            <?php elseif ($d['status'] === 'pending'): ?><span class="badge badge-warning">Generando</span>
                            <?php else: ?><span class="badge badge-danger">Falló</span><?php endif; ?>
                        </td>
                        <td class="row-actions">
                            <a class="btn btn-outline btn-sm" href="<?= e(url('portal/herramienta.php?gen=' . $d['id'])) ?>">Abrir</a>
                            <?php if ($d['status'] === 'done'): ?>
                                <a class="btn btn-ghost btn-sm" href="<?= e(url('portal/descargar.php?gen=' . $d['id'] . '&f=pdf')) ?>">PDF</a>
                            <?php endif; ?>
                            <form method="post" data-confirm="¿Eliminar este documento? No se puede deshacer.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                <button class="btn btn-danger btn-sm" name="action" value="delete">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/_bottom.php'; ?>
