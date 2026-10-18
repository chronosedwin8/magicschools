<?php
/**
 * Tutor con avatar: control de gasto.
 *
 * Esta pantalla existe porque Tavus cobra por minuto creado. Desde aquí se
 * apaga el tutor en seco, se fija el presupuesto del mes, se revisa qué se
 * gastó y se cierra a mano cualquier conversación que haya quedado abierta.
 */
require_once __DIR__ . '/_init.php';

if (is_post()) {
    csrf_check();
    $accion = (string) ($_POST['action'] ?? '');

    if ($accion === 'ajustes') {
        $encendido = !empty($_POST['tutor_enabled']) ? '1' : '0';
        $presupuesto = max(0, min(100000, (int) ($_POST['tutor_budget_minutes'] ?? 0)));
        $prueba = !empty($_POST['tutor_test_mode']) ? '1' : '0';
        setting_save('tutor_enabled', $encendido, (int) $user['id']);
        setting_save('tutor_budget_minutes', (string) $presupuesto, (int) $user['id']);
        setting_save('tutor_test_mode', $prueba, (int) $user['id']);
        app_log('admin', 'Ajustes del tutor', ['encendido' => $encendido, 'presupuesto' => $presupuesto, 'prueba' => $prueba, 'admin' => $user['id']]);
        flash('success', 'Ajustes del tutor guardados.');
        redirect('admin/tutor.php');
    }

    if ($accion === 'cerrar') {
        $cerrada = tutor_end((int) ($_POST['id'] ?? 0), 'admin');
        flash($cerrada ? 'success' : 'error', $cerrada
            ? 'Conversación cerrada. Se contaron ' . tavus_minutes((int) $cerrada['billed_seconds']) . ' minutos.'
            : 'No encontré esa conversación.');
        redirect('admin/tutor.php');
    }

    if ($accion === 'barrer') {
        $h = tutor_sweep();
        flash('success', sprintf('Barrido hecho: %d vencidas, %d por inactividad, %d colgadas, %d huérfanas.',
            $h['vencidas'], $h['inactivas'], $h['colgadas'], $h['huerfanas']));
        redirect('admin/tutor.php');
    }
}

$period = ai_period();
$presupuesto = tutor_budget_seconds();
$usado = tutor_site_used_seconds($period);
$pct = $presupuesto > 0 ? min(100, (int) round($usado / $presupuesto * 100)) : 0;

$mes = db_one(
    'SELECT COUNT(*) n, COALESCE(SUM(billed_seconds),0) s, COALESCE(SUM(cost_micro_usd),0) c
       FROM tutor_sessions WHERE DATE_FORMAT(created_at, "%Y%m") = ? AND status = "ended"',
    [$period]
);
$historico = db_one('SELECT COUNT(*) n, COALESCE(SUM(billed_seconds),0) s, COALESCE(SUM(cost_micro_usd),0) c FROM tutor_sessions WHERE status = "ended"');
$fallidas = (int) db_one('SELECT COUNT(*) c FROM tutor_sessions WHERE status = "failed"')['c'];
$abiertas = db_all(
    'SELECT t.*, u.name, u.email FROM tutor_sessions t LEFT JOIN users u ON u.id = t.user_id
      WHERE t.status IN ("reserved","live","ending") ORDER BY t.id DESC'
);
$ultimas = db_all(
    'SELECT t.*, u.name, u.email, l.institution FROM tutor_sessions t
       LEFT JOIN users u ON u.id = t.user_id
       LEFT JOIN (SELECT li.id, us.institution FROM licenses li JOIN users us ON us.id = li.user_id) l ON l.id = t.license_id
      WHERE t.status IN ("ended","failed") ORDER BY t.id DESC LIMIT 25'
);
$motivos = [
    'docente' => 'La cerró el docente', 'admin' => 'Cerrada desde el panel', 'inactividad' => 'Sin interacción',
    'tope_tiempo' => 'Tiempo agotado', 'cerro_pestana' => 'Cerró la pestaña', 'huerfana' => 'Huérfana (barrido)',
    'cierre_reintentado' => 'Cierre reintentado', 'create_failed' => 'Tavus rechazó la creación',
    'sin_respuesta' => 'Tavus no respondió', 'sin_url' => 'Respuesta incompleta',
];

$active = 'tutor';
$pageTitle = 'Tutor con avatar';
$pageCss = 'assets/css/tools.css';
include __DIR__ . '/../portal/_top.php';
?>
<div class="page-head">
    <div>
        <h1>Tutor con avatar</h1>
        <p class="muted">Control del gasto: Tavus cobra por minuto desde que se crea la conversación.</p>
    </div>
    <form method="post" onsubmit="return confirm('¿Cerrar ahora todas las conversaciones vencidas o inactivas?')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="barrer">
        <button class="btn btn-outline btn-sm">Barrer ahora</button>
    </form>
</div>

<?php if (!tavus_configured()): ?>
    <p class="alert alert-warning">Falta la configuración de Tavus (clave y avatar) en <code>config.php</code>.
        Mientras falte, el tutor no atiende a nadie.</p>
<?php elseif (!setting_on('tutor_enabled', true)): ?>
    <p class="alert alert-warning">El tutor está <strong>apagado</strong>: nadie puede abrir conversaciones.</p>
<?php endif; ?>
<?php if (setting_on('tutor_test_mode', false)): ?>
    <p class="alert alert-info">Modo de prueba activo: las conversaciones se crean sin cobro, pero el avatar no habla.
        Apágalo para atender docentes de verdad.</p>
<?php endif; ?>

<div class="kpis">
    <div class="kpi"><span class="kpi-icon c-purple"><?= icon('reloj', 22) ?></span>
        <div><small>Minutos este mes</small><strong><?= tavus_minutes((int) $mes['s']) ?></strong>
            <small>de <?= (int) ($presupuesto / 60) ?> presupuestados</small>
            <div class="progress"><span style="width: <?= $pct ?>%"></span></div></div></div>
    <div class="kpi"><span class="kpi-icon c-green"><?= icon('documento', 22) ?></span>
        <div><small>Costo del mes</small><strong class="kpi-sm"><?= e(ai_cost_usd((int) $mes['c'])) ?></strong>
            <small><?= (int) $mes['n'] ?> conversaciones</small></div></div>
    <div class="kpi"><span class="kpi-icon c-blue"><?= icon('chispa', 22) ?></span>
        <div><small>Conversaciones abiertas</small><strong><?= count($abiertas) ?></strong>
            <small><?= $fallidas ?> fallidas sin cobro</small></div></div>
    <div class="kpi"><span class="kpi-icon c-orange"><?= icon('idea', 22) ?></span>
        <div><small>Costo acumulado</small><strong class="kpi-sm"><?= e(ai_cost_usd((int) $historico['c'])) ?></strong>
            <small><?= tavus_minutes((int) $historico['s']) ?> minutos</small></div></div>
</div>

<div class="two-col">
    <section class="panel">
        <h2>Ajustes</h2>
        <form method="post" class="form-stack">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="ajustes">
            <label class="check">
                <input type="checkbox" name="tutor_enabled" value="1" <?= setting_on('tutor_enabled', true) ? 'checked' : '' ?>>
                <span><strong>Tutor encendido.</strong> Al desmarcarlo nadie puede abrir conversaciones nuevas
                    (las abiertas se cierran igual por su cuenta).</span>
            </label>
            <label>Presupuesto del mes (minutos)
                <input type="number" name="tutor_budget_minutes" min="0" max="100000" required
                       value="<?= (int) setting_int('tutor_budget_minutes', TAVUS_MONTHLY_BUDGET_MIN) ?>">
                <span class="field-hint">Tope para todo el sitio, por encima de los minutos de cada plan.
                    A US$ <?= e(number_format(TAVUS_COST_PER_MIN_USD, 2, ',', '.')) ?> el minuto, son
                    <?= e(ai_cost_usd(setting_int('tutor_budget_minutes', TAVUS_MONTHLY_BUDGET_MIN) * tavus_cost_per_min_micro())) ?> al mes como máximo.</span>
            </label>
            <label class="check">
                <input type="checkbox" name="tutor_test_mode" value="1" <?= setting_on('tutor_test_mode', false) ? 'checked' : '' ?>>
                <span>Modo de prueba (sin cobro). Sirve para verificar el flujo completo; el avatar no conversa.</span>
            </label>
            <button class="btn btn-primary">Guardar ajustes</button>
        </form>
    </section>

    <section class="panel">
        <h2>Cómo se corta el gasto</h2>
        <ul class="check-list">
            <li>Duración máxima por conversación: <?= (int) TAVUS_MAX_MINUTES ?> minutos (va en la petición a Tavus).</li>
            <li>Sin interacción humana por <?= (int) TAVUS_IDLE_SECONDS ?> s: cierra el navegador, y si no, el barrido.</li>
            <li>Minutos por licencia y por docente, congelados en el plan vendido.</li>
            <li>Presupuesto del sitio: cuando se agota, nadie abre más conversaciones.</li>
            <li>Si Tavus no responde al crear, el barrido busca la conversación por nombre y la cierra.</li>
        </ul>
        <p class="small muted">El barrido debe estar en el cron del servidor:
            <code>* * * * * php <?= e(dirname(__DIR__)) ?>/cron/tutor_sweep.php</code></p>
    </section>
</div>

<?php if ($abiertas): ?>
    <section class="panel">
        <h2>Conversaciones abiertas</h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Docente</th><th>Estado</th><th>Abierta</th><th>Tope</th><th>Última interacción</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($abiertas as $a): ?>
                    <tr>
                        <td><strong><?= e($a['name'] ?? '—') ?></strong><small class="block muted"><?= e($a['email'] ?? '') ?></small></td>
                        <td><span class="badge badge-warning"><?= e($a['status']) ?></span></td>
                        <td class="nowrap"><?= e(fecha_hora($a['created_at'])) ?></td>
                        <td class="nowrap"><?= tavus_minutes((int) $a['max_seconds']) ?> min</td>
                        <td class="nowrap"><?= $a['last_beat_at'] ? e(fecha_hora($a['last_beat_at'])) : '—' ?></td>
                        <td><form method="post" onsubmit="return confirm('¿Cerrar esta conversación ahora?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="cerrar">
                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                            <button class="btn btn-outline btn-sm">Cerrar</button>
                        </form></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<section class="panel">
    <h2>Últimas conversaciones</h2>
    <?php if (!$ultimas): ?>
        <p class="muted">Todavía no hay conversaciones registradas.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Fecha</th><th>Docente</th><th>Tema</th><th>Minutos</th><th>Costo</th><th>Cierre</th></tr></thead>
                <tbody>
                <?php foreach ($ultimas as $u): ?>
                    <tr>
                        <td class="nowrap"><?= e(fecha_hora($u['created_at'])) ?></td>
                        <td><?= e($u['name'] ?? '—') ?><small class="block muted"><?= e($u['institution'] ?? '') ?></small></td>
                        <td><?= e($u['topic'] ?: '—') ?></td>
                        <td class="nowrap"><?= $u['status'] === 'failed' ? '—' : tavus_minutes((int) $u['billed_seconds']) ?></td>
                        <td class="nowrap"><?= $u['status'] === 'failed' ? '—' : e(ai_cost_usd((int) $u['cost_micro_usd'])) ?></td>
                        <td><?= e($motivos[(string) $u['end_reason']] ?? (string) $u['end_reason']) ?>
                            <?php if ($u['error']): ?><small class="block muted"><?= e(mb_substr((string) $u['error'], 0, 80)) ?></small><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../portal/_bottom.php'; ?>
