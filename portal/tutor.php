<?php
/**
 * Tutor con avatar (Daniel).
 *
 * Abrir la conversación cuesta dinero desde el primer segundo, así que esta
 * pantalla solo tiene dos botones: empezar y terminar. El resto —el tope de
 * duración, el aviso de inactividad y el cierre automático— lo maneja el
 * navegador con assets/js/tutor.js y lo respalda el barrido del servidor.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$license = require_tools_access($user);

$sesion = tutor_live_session((int) $user['id']);
$permiso = tutor_allowance($user, $license);
$quota = tutor_quota_for($license);
$period = ai_period();
$usadosLic = tutor_used_seconds((int) $license['id'], null, $period);
$usadosDoc = tutor_used_seconds((int) $license['id'], (int) $user['id'], $period);

if (is_post()) {
    csrf_check();
    $accion = (string) ($_POST['action'] ?? '');

    if ($accion === 'start') {
        if (empty($_POST['acepto'])) {
            flash('error', 'Debes aceptar las condiciones de la conversación antes de empezar.');
            redirect('portal/tutor.php');
        }
        $r = tutor_start($user, $license, (string) ($_POST['topic'] ?? ''));
        if (!$r['ok']) {
            flash('error', $r['error']);
        }
        redirect('portal/tutor.php');
    }

    if ($accion === 'end') {
        $id = (int) ($_POST['id'] ?? 0);
        $s = tutor_session($id);
        if ($s && (int) $s['user_id'] === (int) $user['id']) {
            $cerrada = tutor_end($id, 'docente');
            flash('success', 'Conversación cerrada. Se contaron ' . tavus_minutes((int) ($cerrada['billed_seconds'] ?? 0)) . ' minutos.');
        }
        redirect('portal/tutor.php');
    }
}

// Últimas conversaciones del docente, para que vea en qué gastó sus minutos
$historial = db_all(
    'SELECT * FROM tutor_sessions WHERE user_id = ? AND status IN ("ended","failed") ORDER BY id DESC LIMIT 8',
    [(int) $user['id']]
);

$active = 'tutor';
$pageTitle = 'Tutor con avatar';
$pageCss = 'assets/css/tools.css';
$pageJs = $sesion && $sesion['status'] === 'live' ? ['assets/js/tutor.js'] : [];
include __DIR__ . '/_top.php';
?>
<div class="page-head">
    <div>
        <h1>Tutor con avatar</h1>
        <p class="muted">Conversa con Daniel sobre tu clase: él pregunta, propone y te devuelve la decisión.</p>
    </div>
    <?php if ($quota['license'] > 0): ?>
        <div class="head-meta">
            <span><strong><?= tavus_minutes(max(0, $quota['license'] * 60 - $usadosLic)) ?></strong> min disponibles este mes</span>
            <?php if ($quota['member'] > 0): ?>
                <small class="muted block">Tu tope personal: <?= tavus_minutes(max(0, $quota['member'] * 60 - $usadosDoc)) ?> de <?= (int) $quota['member'] ?> min</small>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($sesion && $sesion['status'] === 'live'): ?>
    <section class="panel tutor-live"
             id="tutor-live"
             data-id="<?= (int) $sesion['id'] ?>"
             data-api="<?= e(url('portal/tutor_api.php')) ?>"
             data-csrf="<?= e(csrf_token()) ?>"
             data-remaining="<?= tutor_remaining($sesion) ?>"
             data-idle-limit="<?= TAVUS_IDLE_SECONDS ?>">
        <div class="tutor-bar">
            <span class="tutor-dot"></span>
            <strong>En conversación con Daniel</strong>
            <span class="tutor-clock">Quedan <b id="tutor-clock"><?= gmdate('i:s', tutor_remaining($sesion)) ?></b></span>
            <form method="post" class="tutor-end">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="end">
                <input type="hidden" name="id" value="<?= (int) $sesion['id'] ?>">
                <button class="btn btn-outline btn-sm">Terminar</button>
            </form>
        </div>

        <div class="tutor-frame">
            <iframe src="<?= e((string) $sesion['conversation_url']) ?>"
                    title="Conversación con el tutor Daniel"
                    allow="camera; microphone; fullscreen; speaker; display-capture; autoplay"
                    allowfullscreen></iframe>
        </div>

        <div class="tutor-idle" id="tutor-idle" hidden>
            <div>
                <strong>¿Sigues ahí?</strong>
                <p class="small">La conversación se cierra en <b id="tutor-idle-count">30</b> segundos para no gastar tus minutos.</p>
            </div>
            <button class="btn btn-primary btn-sm" id="tutor-stay">Sigo aquí</button>
        </div>

        <p class="small muted mt-16">
            Daniel es una inteligencia artificial, no una persona, y no reemplaza al equipo de apoyo de tu institución.
            La llamada no se graba. Se cierra sola a los <?= (int) round(TAVUS_IDLE_SECONDS / 60) ?> minutos sin interacción
            y al agotar el tiempo de la sesión; los minutos se cuentan desde que se abre.
        </p>
    </section>

<?php elseif ($sesion): ?>
    <section class="panel">
        <h2>Cerrando la conversación anterior</h2>
        <p class="muted">Estamos confirmando el cierre con el proveedor. Recarga en unos segundos.</p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="end">
            <input type="hidden" name="id" value="<?= (int) $sesion['id'] ?>">
            <button class="btn btn-outline btn-sm">Forzar cierre</button>
        </form>
    </section>

<?php else: ?>
    <div class="two-col">
        <section class="panel">
            <h2>Empezar una conversación</h2>
            <?php if (!$permiso['ok']): ?>
                <p class="alert alert-warning"><?= e($permiso['error']) ?></p>
                <?php if ($permiso['limit'] === 'plan'): ?>
                    <p class="small muted">Los planes con tutor incluyen minutos de conversación al mes.
                        Escríbenos a <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a> para añadirlo a tu licencia.</p>
                <?php endif; ?>
            <?php else: ?>
                <form method="post" class="form-stack">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="start">
                    <label>¿Qué quieres trabajar hoy? <span class="field-hint">(opcional, ayuda a Daniel a entrar en tema)</span>
                        <input name="topic" maxlength="190" placeholder="Ej.: cómo evaluar fracciones en quinto sin que se desmotiven">
                    </label>
                    <label class="check">
                        <input type="checkbox" name="acepto" value="1" required>
                        <span>Entiendo que Daniel es una inteligencia artificial, que la conversación dura máximo
                            <?= (int) round($permiso['seconds'] / 60) ?> minutos, que se cierra sola tras
                            <?= (int) round(TAVUS_IDLE_SECONDS / 60) ?> minutos sin interacción y que los minutos se
                            descuentan desde que se abre. No compartiré datos personales de estudiantes.</span>
                    </label>
                    <button class="btn btn-primary">Empezar conversación</button>
                    <p class="small muted">Necesitas permitir cámara y micrófono en tu navegador.</p>
                </form>
            <?php endif; ?>
        </section>

        <section class="panel">
            <h2>Cómo aprovecharlo</h2>
            <ul class="check-list">
                <li>Cuéntale el grado, el tema y qué pasó en la última clase.</li>
                <li>Pídele alternativas cuando una actividad no funcionó.</li>
                <li>Ensaya con él la explicación difícil antes de darla.</li>
                <li>Pídele preguntas para revisar un plan o una evaluación.</li>
                <li>Cierra pidiéndole un acuerdo concreto para tu próxima clase.</li>
            </ul>
            <p class="small muted">Daniel no diagnostica estudiantes ni habla de medicamentos. Para casos individuales
                trabaja contigo los ajustes razonables del aula; el diagnóstico es del equipo de apoyo.</p>
        </section>
    </div>
<?php endif; ?>

<?php if ($historial): ?>
    <section class="panel">
        <h2>Tus conversaciones</h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Fecha</th><th>Tema</th><th>Minutos</th><th>Cierre</th></tr></thead>
                <tbody>
                <?php
                $motivos = [
                    'docente' => 'La cerraste tú', 'inactividad' => 'Sin interacción', 'tope_tiempo' => 'Se agotó el tiempo',
                    'cerro_pestana' => 'Cerraste la pestaña', 'huerfana' => 'Cerrada por el sistema',
                    'cierre_reintentado' => 'Cerrada por el sistema', 'create_failed' => 'No se pudo abrir',
                    'sin_respuesta' => 'No se pudo abrir', 'sin_url' => 'No se pudo abrir',
                ];
                foreach ($historial as $h): ?>
                    <tr>
                        <td class="nowrap"><?= e(fecha_hora($h['created_at'])) ?></td>
                        <td><?= e($h['topic'] ?: '—') ?></td>
                        <td class="nowrap"><?= $h['status'] === 'failed' ? '—' : tavus_minutes((int) $h['billed_seconds']) ?></td>
                        <td><?= e($motivos[(string) $h['end_reason']] ?? (string) $h['end_reason']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
<?php include __DIR__ . '/_bottom.php'; ?>
