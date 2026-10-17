<?php
/**
 * Una herramienta: formulario corto, generación y resultado.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

[$user, $license] = require_tools_access();

// --- Generar -----------------------------------------------------------------
if (is_post()) {
    csrf_check();

    // La institución declara que cuenta con la autorización de las familias
    if (($_POST['action'] ?? '') === 'consent') {
        $destino = ai_tool((string) ($_POST['h'] ?? '')) ? 'portal/herramienta.php?h=' . $_POST['h'] : 'portal/herramientas.php';
        if (empty($_POST['declaro'])) {
            flash('error', 'Debes marcar la declaración para continuar.');
        } else {
            ai_consent_record((int) $license['id'], (int) $user['id']);
            flash('success', 'Declaración registrada. Ya puedes usar esta herramienta.');
        }
        redirect($destino);
    }

    // Ajustar un documento ya generado (más corto, más simple, otro tono…)
    if (($_POST['action'] ?? '') === 'refine') {
        $previa = ai_generation((int) ($_POST['gen'] ?? 0), $user);
        $ajustes = [
            'corto'     => ['más corto', 'Hazlo más corto, conservando lo esencial.'],
            'simple'    => ['más simple', 'Simplifica el lenguaje para que sea más fácil de entender.'],
            'detallado' => ['más detallado', 'Amplíalo con más detalle y ejemplos concretos.'],
            'formal'    => ['tono formal', 'Dale un tono más formal e institucional.'],
            'cercano'   => ['tono cercano', 'Dale un tono más cercano y cálido.'],
            'familias'  => ['para las familias', 'Reescríbelo para que lo entiendan las familias, sin tecnicismos.'],
        ];
        $clave = (string) ($_POST['ajuste'] ?? '');
        if (!$previa || $previa['status'] !== 'done' || !isset($ajustes[$clave])) {
            flash('error', 'No pudimos aplicar ese ajuste.');
            redirect('portal/herramientas.php');
        }
        if ($error = ai_quota_check($license, $user)) {
            flash('error', $error);
            redirect('portal/herramienta.php?gen=' . $previa['id']);
        }

        $tool = ai_tool('refinar');
        $input = [
            'texto'  => (string) $previa['output_text'],
            'ajuste'   => $ajustes[$clave][1],
            'base'     => mb_substr((string) ($previa['title'] ?: $previa['tool_name']), 0, 120),
            'etiqueta' => $ajustes[$clave][0],
        ];
        $id = ai_start_generation($license, $user, $tool, $input, ($tool['title'])($input));
        app_log('ai', 'Ajuste solicitado', ['origen' => $previa['id'], 'nuevo' => $id, 'ajuste' => $clave]);
        ai_dispatch($id, $tool, $input, ai_build_context($user, $license), 'portal/herramienta.php?gen=' . $id);
    }

    $tool = ai_tool((string) ($_POST['h'] ?? ''));
    if (!$tool) {
        flash('error', 'Esa herramienta no existe.');
        redirect('portal/herramientas.php');
    }

    if (!empty($tool['consent']) && !ai_consent_given((int) $license['id'])) {
        flash('error', 'Antes de usar esta herramienta, la institución debe registrar la declaración de autorización.');
        redirect('portal/herramienta.php?h=' . $tool['slug']);
    }

    [$input, $errors] = ai_validate_input($tool, $_POST);
    if ($error = ai_quota_check($license, $user)) {
        $errors[] = $error;
    }

    if ($errors) {
        set_old($_POST);
        foreach ($errors as $e) {
            flash('error', $e);
        }
        redirect('portal/herramienta.php?h=' . $tool['slug']);
    }

    clear_old();
    $title = isset($tool['title']) ? ($tool['title'])($input) : $tool['name'];
    $id = ai_start_generation($license, $user, $tool, $input, $title);
    $ctx = ai_build_context($user, $license);
    app_log('ai', 'Generación iniciada', ['id' => $id, 'herramienta' => $tool['slug'], 'usuario' => $user['id']]);

    // Responde ya y genera después (o genera y luego responde, si el servidor no lo permite)
    ai_dispatch($id, $tool, $input, $ctx, 'portal/herramienta.php?gen=' . $id);
}

// --- Ver el resultado --------------------------------------------------------
$genId = (int) ($_GET['gen'] ?? 0);
$gen = null;
if ($genId > 0) {
    ai_expire_stale();
    $gen = ai_generation($genId, $user);
    if (!$gen) {
        flash('error', 'No encontramos ese documento.');
        redirect('portal/herramientas.php');
    }
    $tool = ai_tool($gen['tool_slug']) ?? ['name' => $gen['tool_name'], 'slug' => $gen['tool_slug'], 'icon' => 'documento', 'color' => 'purple', 'exports' => ['html'], 'fields' => []];
} else {
    $tool = ai_tool((string) ($_GET['h'] ?? ''));
    if (!$tool) {
        redirect('portal/herramientas.php');
    }
}

$active = 'herramientas';
$pageTitle = $tool['name'];
$pageCss = 'assets/css/tools.css';
$pageJs = 'assets/js/tools.js';
include __DIR__ . '/_top.php';
?>
<a class="page-back" href="<?= e(url('portal/herramientas.php')) ?>">← Todas las herramientas</a>

<div class="tool-head">
    <span class="tool-icon c-<?= e($tool['color'] ?? 'purple') ?>"><?= icon($tool['icon'] ?? 'documento', 24) ?></span>
    <div>
        <h1><?= e($tool['name']) ?></h1>
        <?php if (!$gen && !empty($tool['desc'])): ?><p class="muted"><?= e($tool['desc']) ?></p><?php endif; ?>
    </div>
</div>

<?php if ($gen && $gen['status'] === 'pending'): ?>
    <!-- Generando ------------------------------------------------------- -->
    <section class="panel generating" data-poll="<?= e(url('portal/estado.php?id=' . $gen['id'])) ?>">
        <div class="spinner"></div>
        <h2>Creando tu documento…</h2>
        <p class="muted">Suele tardar entre 15 y 40 segundos. Puedes quedarte aquí o volver más tarde:
            el documento queda guardado en <a href="<?= e(url('portal/documentos.php')) ?>">Mis documentos</a>.</p>
    </section>

<?php elseif ($gen && $gen['status'] === 'error'): ?>
    <!-- Falló ----------------------------------------------------------- -->
    <section class="panel">
        <div class="alert alert-error"><?= e($gen['error'] ?: 'No pudimos generar el documento.') ?></div>
        <p class="muted">No se descontó de tu cupo. Puedes intentarlo de nuevo con los mismos datos.</p>
        <form method="post" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="h" value="<?= e($gen['tool_slug']) ?>">
            <?php foreach ((array) json_decode((string) $gen['input_json'], true) as $k => $v): ?>
                <input type="hidden" name="<?= e($k) ?>" value="<?= e((string) $v) ?>">
            <?php endforeach; ?>
            <button class="btn btn-primary">Intentar de nuevo</button>
            <a class="btn btn-ghost" href="<?= e(url('portal/herramienta.php?h=' . $gen['tool_slug'])) ?>">Cambiar los datos</a>
        </form>
    </section>

<?php elseif ($gen): ?>
    <!-- Resultado -------------------------------------------------------- -->
    <div class="result-layout">
        <article class="panel result-doc">
            <div class="result-actions">
                <button type="button" class="btn btn-outline btn-sm" data-copy-doc>Copiar texto</button>
                <?php
                $etiquetas = ['pdf' => 'PDF', 'docx' => 'Word', 'pptx' => 'PowerPoint', 'html' => 'HTML'];
                foreach ((array) ($tool['exports'] ?? ['html']) as $formato):
                    if (!isset($etiquetas[$formato])) continue; ?>
                    <a class="btn btn-outline btn-sm" href="<?= e(url('portal/descargar.php?gen=' . $gen['id'] . '&f=' . $formato)) ?>">
                        <?= e($etiquetas[$formato]) ?></a>
                <?php endforeach; ?>
                <a class="btn btn-primary btn-sm" href="<?= e(url('portal/herramienta.php?h=' . $gen['tool_slug'])) ?>">Crear otro</a>
            </div>
            <div class="doc" id="doc"><?= md_to_html((string) $gen['output_text']) ?></div>

            <div class="refine-bar">
                <span class="refine-label">¿Quieres ajustarlo?</span>
                <?php foreach (['corto' => 'Más corto', 'simple' => 'Más simple', 'detallado' => 'Más detallado',
                                'formal' => 'Más formal', 'cercano' => 'Más cercano', 'familias' => 'Para las familias'] as $clave => $texto): ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="refine">
                        <input type="hidden" name="gen" value="<?= (int) $gen['id'] ?>">
                        <input type="hidden" name="ajuste" value="<?= e($clave) ?>">
                        <button class="btn btn-ghost btn-sm"><?= e($texto) ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
            <p class="doc-note">Borrador generado con inteligencia artificial el <?= fecha($gen['created_at'], true) ?>.
                Revísalo y ajústalo antes de usarlo en clase.</p>
        </article>

        <aside class="panel result-side">
            <h2>Datos que usaste</h2>
            <dl class="result-meta">
                <?php foreach ((array) json_decode((string) $gen['input_json'], true) as $k => $v):
                    if (trim((string) $v) === '') continue;
                    $campo = null;
                    foreach ((array) ($tool['fields'] ?? []) as $f) {
                        if ($f['name'] === $k) { $campo = $f; break; }
                    } ?>
                    <div>
                        <dt><?= e($campo['label'] ?? ucfirst($k)) ?></dt>
                        <dd><?= e(mb_strimwidth((string) $v, 0, 220, '…')) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
            <form method="post" class="mt-24">
                <?= csrf_field() ?>
                <input type="hidden" name="h" value="<?= e($gen['tool_slug']) ?>">
                <?php foreach ((array) json_decode((string) $gen['input_json'], true) as $k => $v): ?>
                    <input type="hidden" name="<?= e($k) ?>" value="<?= e((string) $v) ?>">
                <?php endforeach; ?>
                <button class="btn btn-outline btn-block btn-sm">Generar otra versión</button>
            </form>
        </aside>
    </div>

<?php elseif (!empty($tool['consent']) && !ai_consent_given((int) $license['id'])): ?>
    <!-- Declaración previa ------------------------------------------------ -->
    <section class="panel consent-gate">
        <h2>Antes de usar esta herramienta</h2>
        <p>Esta herramienta trata <strong>datos personales de un estudiante</strong>, y en el caso del PIAR también
            <strong>datos sensibles</strong> relacionados con su condición. La Ley 1581 de 2012 exige autorización
            previa, expresa e informada de los padres o acudientes.</p>
        <ul class="checklist">
            <li>La institución es la responsable de recoger y conservar esa autorización.</li>
            <li>Escribe solo los datos necesarios para construir el documento.</li>
            <li>Puedes usar iniciales o un alias en las herramientas que no exigen identificar al estudiante.</li>
            <li>Los documentos quedan en el portal de tu institución y puedes borrarlos cuando quieras.</li>
        </ul>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="consent">
            <input type="hidden" name="h" value="<?= e($tool['slug']) ?>">
            <label class="check declaracion">
                <input type="checkbox" name="declaro" value="1" required>
                <span>Declaro que <?= e($license['institution'] ?? 'mi institución') ?> cuenta con la autorización de los
                    padres o acudientes para tratar los datos de los estudiantes en estas herramientas, y que conoce el
                    <a href="<?= e(url('legal.php#privacidad')) ?>" target="_blank">aviso de privacidad</a>.</span>
            </label>
            <button class="btn btn-primary btn-lg">Registrar la declaración y continuar</button>
        </form>
        <p class="muted small">Queda registrada con tu usuario, la fecha y la dirección IP. Se hace una sola vez por licencia.</p>
    </section>

<?php else: ?>
    <!-- Formulario ------------------------------------------------------- -->
    <form method="post" class="panel tool-form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="h" value="<?= e($tool['slug']) ?>">
        <?php foreach ($tool['fields'] as $f): ?>
            <label class="<?= in_array($f['type'], ['checks', 'check'], true) ? 'field-group' : '' ?>">
                <?php if ($f['type'] !== 'check'): ?>
                    <?= e($f['label']) ?><?php if (empty($f['required'])): ?> <span class="field-hint">(opcional)</span><?php endif; ?>
                <?php endif; ?>
                <?php if ($f['type'] === 'checks'): ?>
                    <div class="check-grid">
                        <?php foreach ($f['options'] as $op): ?>
                            <label class="check check-item">
                                <input type="checkbox" name="<?= e($f['name']) ?>[]" value="<?= e($op) ?>">
                                <span><?= e($op) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($f['type'] === 'check'): ?>
                    <label class="check declaracion">
                        <input type="checkbox" name="<?= e($f['name']) ?>" value="1" <?= !empty($f['required']) ? 'required' : '' ?>>
                        <span><?= e($f['text'] ?? $f['label']) ?></span>
                    </label>
                <?php elseif ($f['type'] === 'select'): ?>
                    <select name="<?= e($f['name']) ?>" <?= !empty($f['required']) ? 'required' : '' ?>>
                        <option value="">Selecciona…</option>
                        <?php foreach ($f['options'] as $op): ?>
                            <option value="<?= e($op) ?>" <?= old($f['name']) === $op ? 'selected' : '' ?>><?= e($op) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($f['type'] === 'textarea'): ?>
                    <textarea name="<?= e($f['name']) ?>" rows="5" maxlength="<?= (int) $f['max'] ?>"
                              placeholder="<?= e($f['placeholder'] ?? '') ?>"><?= old($f['name']) ?></textarea>
                <?php else: ?>
                    <input name="<?= e($f['name']) ?>" maxlength="<?= (int) $f['max'] ?>"
                           value="<?= old($f['name']) ?>" placeholder="<?= e($f['placeholder'] ?? '') ?>"
                           <?= !empty($f['required']) ? 'required' : '' ?>>
                <?php endif; ?>
                <?php if (!empty($f['help'])): ?><small class="field-help"><?= e($f['help']) ?></small><?php endif; ?>
            </label>
        <?php endforeach; ?>
        <button class="btn btn-primary btn-lg btn-block">Generar</button>
        <p class="muted small center">El resultado es un borrador que debes revisar antes de usarlo.</p>
    </form>
<?php endif; ?>
<?php clear_old(); include __DIR__ . '/_bottom.php'; ?>
