<?php
/**
 * Costos de IA y verificación del margen.
 * Toma los costos REALES medidos en las generaciones, no estimaciones.
 */
require_once __DIR__ . '/_init.php';

// Parámetros del cálculo (se pueden ajustar en pantalla)
$comision = max(0.0, min(30.0, (float) ($_GET['comision'] ?? 4.15)));   // % que cobra la pasarela
$tasa     = max(1, (int) ($_GET['tasa'] ?? 4000));                      // COP por dólar
$margen   = max(1.0, min(95.0, (float) ($_GET['margen'] ?? 60.0)));     // % de margen objetivo

$mes = ai_period();
$porHerramienta = db_all(
    'SELECT tool_slug, tool_name, COUNT(*) n,
            AVG(cost_micro_usd) promedio, SUM(cost_micro_usd) total,
            AVG(prompt_tokens) tok_in, AVG(output_tokens) tok_out
       FROM ai_generations WHERE status = "done" GROUP BY tool_slug, tool_name ORDER BY n DESC'
);
$global = db_one('SELECT COUNT(*) n, SUM(cost_micro_usd) total, AVG(cost_micro_usd) promedio FROM ai_generations WHERE status = "done"');
$delMes = db_one('SELECT COUNT(*) n, SUM(cost_micro_usd) total FROM ai_generations WHERE status = "done" AND DATE_FORMAT(created_at, "%Y%m") = ?', [$mes]);
$fallidas = (int) db_one('SELECT COUNT(*) c FROM ai_generations WHERE status = "error"')['c'];
$promedioGlobal = (int) ($global['promedio'] ?? 0);

/** Precio mínimo que respeta el margen objetivo, dado un costo mensual en micro-dólares. */
function precio_minimo_cop(int $costoMicro, float $comision, float $margen, int $tasa): float
{
    $denominador = (1 - $margen / 100) - $comision / 100;
    if ($denominador <= 0) {
        return 0.0;
    }
    return ($costoMicro / 1000000) / $denominador * $tasa;
}

$active = 'costos';
$pageTitle = 'Costos y margen';
include __DIR__ . '/../portal/_top.php';
?>
<div class="page-head">
    <div>
        <h1>Costos y margen</h1>
        <p class="muted">Lo que cuesta cada generación y el precio mínimo que mantiene tu margen.</p>
    </div>
</div>

<div class="kpis">
    <div class="kpi"><span class="kpi-icon c-purple"><?= icon('chispa', 22) ?></span>
        <div><small>Generaciones totales</small><strong><?= (int) $global['n'] ?></strong><small><?= $fallidas ?> fallidas (sin costo)</small></div></div>
    <div class="kpi"><span class="kpi-icon c-green"><?= icon('documento', 22) ?></span>
        <div><small>Costo acumulado</small><strong class="kpi-sm"><?= e(ai_cost_usd((int) $global['total'])) ?></strong></div></div>
    <div class="kpi"><span class="kpi-icon c-blue"><?= icon('reloj', 22) ?></span>
        <div><small>Este mes</small><strong class="kpi-sm"><?= e(ai_cost_usd((int) $delMes['total'])) ?></strong><small><?= (int) $delMes['n'] ?> generaciones</small></div></div>
    <div class="kpi"><span class="kpi-icon c-orange"><?= icon('idea', 22) ?></span>
        <div><small>Costo promedio</small><strong class="kpi-sm"><?= e(ai_cost_usd($promedioGlobal)) ?></strong><small>por generación</small></div></div>
</div>

<form class="panel filters" method="get">
    <label style="margin:0">Comisión de la pasarela (%)<input type="number" step="0.01" name="comision" value="<?= e((string) $comision) ?>"></label>
    <label style="margin:0">Dólar (COP)<input type="number" name="tasa" value="<?= (int) $tasa ?>"></label>
    <label style="margin:0">Margen objetivo (%)<input type="number" step="1" name="margen" value="<?= e((string) $margen) ?>"></label>
    <button class="btn btn-primary btn-sm">Recalcular</button>
</form>

<section class="panel">
    <h2>Costo por herramienta</h2>
    <?php if (!$porHerramienta): ?>
        <p class="muted">Todavía no hay generaciones medidas.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Herramienta</th><th>Usos</th><th>Tokens (entrada / salida)</th><th>Costo promedio</th><th>Costo total</th></tr></thead>
                <tbody>
                <?php foreach ($porHerramienta as $h): ?>
                    <tr>
                        <td><strong><?= e($h['tool_name']) ?></strong></td>
                        <td><?= (int) $h['n'] ?></td>
                        <td class="nowrap"><?= number_format((float) $h['tok_in'], 0, ',', '.') ?> / <?= number_format((float) $h['tok_out'], 0, ',', '.') ?></td>
                        <td class="nowrap"><?= e(ai_cost_usd((int) $h['promedio'])) ?></td>
                        <td class="nowrap"><?= e(ai_cost_usd((int) $h['total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Tutor con avatar</h2>
    <?php $tut = db_one('SELECT COUNT(*) n, COALESCE(SUM(billed_seconds),0) s, COALESCE(SUM(cost_micro_usd),0) c FROM tutor_sessions WHERE status = "ended"');
          $tutMes = db_one('SELECT COALESCE(SUM(billed_seconds),0) s, COALESCE(SUM(cost_micro_usd),0) c FROM tutor_sessions WHERE status = "ended" AND DATE_FORMAT(created_at, "%Y%m") = ?', [$mes]); ?>
    <p class="muted small">Se cobra por minuto creado, no por minuto hablado. Tarifa de referencia:
        US$ <?= e(number_format(TAVUS_COST_PER_MIN_USD, 2, ',', '.')) ?> por minuto.</p>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Periodo</th><th>Conversaciones</th><th>Minutos</th><th>Costo</th></tr></thead>
            <tbody>
                <tr><td>Este mes</td><td>—</td><td><?= tavus_minutes((int) $tutMes['s']) ?></td><td><?= e(ai_cost_usd((int) $tutMes['c'])) ?></td></tr>
                <tr><td>Acumulado</td><td><?= (int) $tut['n'] ?></td><td><?= tavus_minutes((int) $tut['s']) ?></td><td><?= e(ai_cost_usd((int) $tut['c'])) ?></td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2>Margen de cada plan</h2>
    <p class="muted small">El precio mínimo se calcula sobre el costo de IA a cuota llena, descontando la comisión de la pasarela
        y dejando el <?= e((string) $margen) ?> % de margen. Fórmula: precio = costo ÷ (<?= number_format((1 - $margen / 100) - $comision / 100, 4, ',', '.') ?>).</p>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Plan</th><th>Cuota mensual</th><th>Costo de IA a cuota llena</th><th>Precio mínimo al <?= (int) $margen ?> %</th><th>Precio publicado</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($PLANS as $p):
                $cuota = (int) ($p['ai_monthly_quota'] ?? 0);
                $minutos = (int) ($p['tutor_minutes'] ?? 0);
                // Peor caso: se usa toda la cuota de IA y todos los minutos de tutor.
                $costoMes = $promedioGlobal * $cuota + $minutos * tavus_cost_per_min_micro();
                $meses = max(1, (int) ($p['months'] ?? 12));
                $costoVigencia = $costoMes * $meses;
                $minimo = precio_minimo_cop((int) $costoVigencia, $comision, $margen, $tasa);
                $ok = $minimo > 0 && (float) $p['price'] >= $minimo; ?>
                <tr>
                    <td><strong><?= e($p['name']) ?></strong><small class="block muted"><?= (int) $meses ?> meses</small></td>
                    <td><?= $cuota > 0 ? number_format($cuota, 0, ',', '.') . ' generaciones' : 'sin límite' ?>
                        <small class="block muted"><?= $minutos > 0 ? number_format($minutos, 0, ',', '.') . ' min de tutor' : 'sin tutor' ?></small></td>
                    <td class="nowrap"><?= e(ai_cost_usd((int) $costoVigencia)) ?><small class="block muted"><?= e(money_short((int) round($costoVigencia / 1000000 * $tasa))) ?></small></td>
                    <td class="nowrap"><strong><?= e(money_short((int) round($minimo))) ?></strong></td>
                    <td class="nowrap"><?= e(money_short((float) $p['price'])) ?></td>
                    <td><?php if ($cuota === 0): ?><span class="badge badge-muted">sin cuota</span>
                        <?php elseif ($ok): ?><span class="badge badge-success">margen cubierto</span>
                        <?php else: ?><span class="badge badge-danger">precio bajo</span><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="muted small mt-24">El costo promedio sale de <?= (int) $global['n'] ?> generaciones reales;
        cuantas más se acumulen, más confiable es el cálculo. El tutor se cuenta a
        US$ <?= e(number_format(TAVUS_COST_PER_MIN_USD, 2, ',', '.')) ?> el minuto, que es lo más caro de cualquier plan:
        los minutos reales están en <a href="<?= e(url('admin/tutor.php')) ?>">Tutor con avatar</a>.</p>
</section>
<?php include __DIR__ . '/../portal/_bottom.php'; ?>
