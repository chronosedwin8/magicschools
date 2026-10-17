<?php
/**
 * Catálogo de herramientas de IA del docente.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

[$user, $license] = require_tools_access();

$tools = ai_tools();
$categorias = ai_tool_categories();
$quota = ai_quota_for($license);
$usoMio = (int) ai_usage((int) $license['id'], (int) $user['id'])['generations'];
$usoInst = (int) ai_usage((int) $license['id'], 0)['generations'];
$restantes = $quota['member'] > 0 ? max(0, $quota['member'] - $usoMio) : null;
$pct = $quota['member'] > 0 ? min(100, (int) round($usoMio / $quota['member'] * 100)) : 0;
$recientes = db_all(
    'SELECT id, tool_name, title, status, created_at FROM ai_generations
      WHERE user_id = ? ORDER BY created_at DESC LIMIT 5',
    [$user['id']]
);

$active = 'herramientas';
$pageTitle = 'Herramientas';
$pageCss = 'assets/css/tools.css';
include __DIR__ . '/_top.php';
?>
<div class="page-head">
    <div>
        <h1>Herramientas</h1>
        <p class="muted">Elige qué necesitas crear. Cada herramienta pide solo lo indispensable.</p>
    </div>
    <?php if ($restantes !== null): ?>
        <div class="quota-chip">
            <strong><?= $restantes ?></strong> de <?= $quota['member'] ?> generaciones este mes
            <div class="progress"><span style="width: <?= $pct ?>%"></span></div>
        </div>
    <?php endif; ?>
</div>

<?php if ($restantes === 0): ?>
    <div class="alert alert-warning">Alcanzaste tu cupo del mes. Se renueva el día 1; si necesitas más, pídeselo a quien administra la licencia.</div>
<?php endif; ?>

<div class="tool-tabs" role="tablist">
    <button class="tab active" data-filter="all" type="button">Todas</button>
    <?php foreach ($categorias as $key => $label): ?>
        <?php if (array_filter($tools, fn($t) => $t['category'] === $key)): ?>
            <button class="tab" data-filter="<?= e($key) ?>" type="button"><?= e($label) ?></button>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<div class="tools-grid portal-tools">
    <?php foreach ($tools as $t): ?>
        <a class="tool-card" data-cat="<?= e($t['category']) ?>" href="<?= e(url('portal/herramienta.php?h=' . $t['slug'])) ?>">
            <span class="tool-icon c-<?= e($t['color']) ?>"><?= icon($t['icon'], 22) ?></span>
            <h3><?= e($t['name']) ?></h3>
            <p><?= e($t['desc']) ?></p>
            <span class="tool-link">Usar herramienta <span aria-hidden="true">→</span></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="two-col mt-24">
    <section class="panel">
        <div class="panel-head"><h2>Lo último que creaste</h2>
            <a class="btn btn-ghost btn-sm" href="<?= e(url('portal/documentos.php')) ?>">Ver todo</a></div>
        <?php if (!$recientes): ?>
            <p class="muted">Todavía no has creado nada. Empieza por un plan de clase: toma menos de un minuto.</p>
        <?php else: ?>
            <ul class="recent-list">
                <?php foreach ($recientes as $r): ?>
                    <li>
                        <a href="<?= e(url('portal/herramienta.php?gen=' . $r['id'])) ?>"><?= e($r['title'] ?: $r['tool_name']) ?></a>
                        <small class="muted"><?= e($r['tool_name']) ?> · <?= fecha($r['created_at'], true) ?>
                            <?php if ($r['status'] === 'error'): ?><span class="badge badge-danger">falló</span><?php endif; ?>
                            <?php if ($r['status'] === 'pending'): ?><span class="badge badge-warning">generando</span><?php endif; ?>
                        </small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <section class="panel">
        <h2>Cómo sacarle provecho</h2>
        <ul class="checklist">
            <li>Escribe el tema como se lo dirías a un colega: la IA se encarga del formato.</li>
            <li>Si pegas el texto visto en clase, el resultado sale de ahí y no de conocimiento general.</li>
            <li>Todo resultado es un borrador: revísalo y ajústalo antes de llevarlo al aula.</li>
            <li>Completa tu perfil en <a href="<?= e(url('portal/cuenta.php')) ?>">Mi cuenta</a> y no tendrás que repetir tu grado y área en cada herramienta.</li>
        </ul>
        <?php if ($quota['license'] > 0 && !is_teacher($user)): ?>
            <p class="muted small">Consumo de la institución este mes: <strong><?= $usoInst ?></strong> de <?= $quota['license'] ?>.</p>
        <?php endif; ?>
    </section>
</div>
<?php include __DIR__ . '/_bottom.php'; ?>
