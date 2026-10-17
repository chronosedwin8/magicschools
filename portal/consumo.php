<?php
/**
 * Consumo de las herramientas de IA en la institución.
 * Lo ve quien administra la licencia, no los docentes.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
if (is_teacher($user)) {
    flash('info', 'El consumo de la institución lo consulta quien administra la licencia.');
    redirect('portal/herramientas.php');
}
$license = current_license($user);
if (!$license) {
    flash('warning', 'Necesitas una licencia activa para ver el consumo.');
    redirect('portal/licencias.php');
}

$period = $_GET['p'] ?? ai_period();
if (!preg_match('/^\d{6}$/', (string) $period)) {
    $period = ai_period();
}
$quota = ai_quota_for($license);
$total = ai_usage((int) $license['id'], 0, $period);
$pct = $quota['license'] > 0 ? min(100, (int) round($total['generations'] / $quota['license'] * 100)) : 0;

// Consumo por docente en el periodo
$porDocente = db_all(
    'SELECT u.id, u.name, u.email, m.area,
            COALESCE(x.generations, 0) AS generations, COALESCE(x.cost_micro_usd, 0) AS costo
       FROM license_members m
       LEFT JOIN users u ON u.id = m.user_id
       LEFT JOIN ai_usage_month x ON x.license_id = ? AND x.user_id = u.id AND x.period = ?
      WHERE m.license_id = ?
      ORDER BY generations DESC, m.name',
    [$license['id'], $period, $license['id']]
);
$porHerramienta = db_all(
    'SELECT tool_name, COUNT(*) n, SUM(cost_micro_usd) costo
       FROM ai_generations
      WHERE license_id = ? AND DATE_FORMAT(created_at, "%Y%m") = ? AND status = "done"
      GROUP BY tool_name ORDER BY n DESC',
    [$license['id'], $period]
);
$meses = db_all(
    'SELECT period, generations FROM ai_usage_month WHERE license_id = ? AND user_id = 0 ORDER BY period DESC LIMIT 6',
    [$license['id']]
);

$active = 'consumo';
$pageTitle = 'Consumo';
$pageCss = 'assets/css/tools.css';
include __DIR__ . '/_top.php';
?>
<div class="page-head">
    <div>
        <h1>Consumo de herramientas</h1>
        <p class="muted">Cuánto está usando la IA tu equipo docente.</p>
    </div>
    <form method="get" class="filters" style="padding:0;border:0;background:none">
        <select name="p" onchange="this.form.submit()">
            <?php foreach ($meses ?: [['period' => ai_period()]] as $m):
                $et = DateTime::createFromFormat('Ym', $m['period']); ?>
                <option value="<?= e($m['period']) ?>" <?= $period === $m['period'] ? 'selected' : '' ?>>
                    <?= $et ? e(fecha($et->format('Y-m-01'))) : e($m['period']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="kpis">
    <div class="kpi"><span class="kpi-icon c-purple"><?= icon('chispa', 22) ?></span>
        <div><small>Generaciones del mes</small><strong><?= (int) $total['generations'] ?></strong>
            <?php if ($quota['license'] > 0): ?><small>de <?= $quota['license'] ?> disponibles</small><?php endif; ?></div></div>
    <div class="kpi"><span class="kpi-icon c-green"><?= icon('docente', 22) ?></span>
        <div><small>Docentes activos</small><strong><?= count(array_filter($porDocente, fn($d) => $d['generations'] > 0)) ?></strong>
            <small>de <?= count($porDocente) ?> registrados</small></div></div>
    <div class="kpi"><span class="kpi-icon c-blue"><?= icon('documento', 22) ?></span>
        <div><small>Herramienta más usada</small><strong class="kpi-sm"><?= e($porHerramienta[0]['tool_name'] ?? '—') ?></strong>
            <small><?= isset($porHerramienta[0]) ? (int) $porHerramienta[0]['n'] . ' usos' : 'sin datos' ?></small></div></div>
    <div class="kpi"><span class="kpi-icon c-orange"><?= icon('reloj', 22) ?></span>
        <div><small>Cupo usado</small><strong><?= $quota['license'] > 0 ? $pct . '%' : '—' ?></strong>
            <div class="progress"><span style="width: <?= $pct ?>%"></span></div></div></div>
</div>

<div class="two-col">
    <section class="panel">
        <h2>Por herramienta</h2>
        <?php if (!$porHerramienta): ?>
            <p class="muted">Todavía nadie ha usado las herramientas este mes.</p>
        <?php else: ?>
            <?php $max = max(array_column($porHerramienta, 'n')); ?>
            <?php foreach ($porHerramienta as $h): ?>
                <div class="bar-row">
                    <span><?= e($h['tool_name']) ?></span>
                    <div class="bar"><span style="width: <?= (int) round($h['n'] / $max * 100) ?>%"></span></div>
                    <strong><?= (int) $h['n'] ?></strong>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="panel panel-wide">
        <h2>Por docente</h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Docente</th><th>Área</th><th>Generaciones</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($porDocente as $d): ?>
                    <tr>
                        <td><strong><?= e($d['name'] ?? '—') ?></strong><small class="block muted"><?= e($d['email'] ?? '') ?></small></td>
                        <td><?= e($d['area'] ?: '—') ?></td>
                        <td><?= (int) $d['generations'] ?><?= $quota['member'] > 0 ? ' <small class="muted">/ ' . $quota['member'] . '</small>' : '' ?></td>
                        <td><?php if ($d['name'] === null): ?><span class="badge badge-muted">Sin activar</span>
                            <?php elseif ((int) $d['generations'] === 0): ?><span class="badge badge-warning">Sin usar</span>
                            <?php else: ?><span class="badge badge-success">Activo</span><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$porDocente): ?><tr><td colspan="4" class="muted">Aún no has registrado docentes en la licencia.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php include __DIR__ . '/_bottom.php'; ?>
