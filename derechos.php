<?php
/**
 * Formulario para ejercer los derechos sobre los datos personales
 * (Ley 1581 de 2012: consultas y reclamos; RGPD para titulares en la UE).
 */
require_once __DIR__ . '/includes/bootstrap.php';

$KINDS = [
    'acceso'       => 'Conocer qué datos míos tienen',
    'actualizar'   => 'Actualizar o corregir mis datos',
    'supresion'    => 'Eliminar mis datos',
    'revocatoria'  => 'Revocar la autorización que di',
    'copia'        => 'Obtener una copia de mi información',
    'reclamo'      => 'Presentar un reclamo por el uso de mis datos',
    'otro'         => 'Otra solicitud',
];

$errors = [];
if (is_post()) {
    csrf_check();
    set_old($_POST);

    // Trampa para robots
    if (!empty($_POST['website'])) {
        flash('success', 'Recibimos tu solicitud.');
        redirect('derechos.php');
    }

    $kind = isset($KINDS[$_POST['kind'] ?? '']) ? $_POST['kind'] : '';
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $detail = trim((string) ($_POST['detail'] ?? ''));

    if ($kind === '')                                 $errors[] = 'Selecciona qué quieres solicitar.';
    if ($name === '' || mb_strlen($name) > 120)       $errors[] = 'Escribe tu nombre completo.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = 'Escribe un correo electrónico válido.';
    if (mb_strlen($detail) > 3000)                    $errors[] = 'La descripción es demasiado larga.';
    if (empty($_POST['titular']))                     $errors[] = 'Debes declarar que eres el titular o su representante.';

    if (!$errors) {
        db_exec(
            'INSERT INTO data_requests (kind, name, email, document, institution, detail, ip_hash)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $kind, mb_substr($name, 0, 120), mb_substr($email, 0, 190),
                mb_substr(trim((string) ($_POST['document'] ?? '')), 0, 40) ?: null,
                mb_substr(trim((string) ($_POST['institution'] ?? '')), 0, 190) ?: null,
                $detail !== '' ? $detail : null,
                hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . BRAND_NAME),
            ]
        );
        app_log('datos', 'Solicitud de derechos recibida', ['tipo' => $kind]);
        clear_old();
        flash('success', 'Recibimos tu solicitud. Te responderemos al correo que indicaste dentro de los plazos de ley.');
        redirect('derechos.php');
    }
}

$pageTitle = 'Ejercer mis derechos sobre mis datos | ' . BRAND_NAME;
$bodyClass = 'page-simple';
include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/nav.php';
?>
<main class="legal">
    <div class="container narrow">
        <div class="card">
            <h1>Tus datos personales</h1>
            <p class="muted">Desde aquí puedes pedirnos cualquier cosa sobre la información que tenemos de ti. Atendemos
                <strong>consultas en 10 días hábiles</strong> y <strong>reclamos en 15 días hábiles</strong>, conforme a la
                Ley 1581 de 2012. Si eres titular en la Unión Europea, también aplican los plazos del RGPD.</p>

            <?= render_flashes() ?>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>

            <form method="post" novalidate>
                <?= csrf_field() ?>
                <label>¿Qué necesitas?
                    <select name="kind" required>
                        <option value="">Selecciona una opción</option>
                        <?php foreach ($KINDS as $k => $label): ?>
                            <option value="<?= e($k) ?>" <?= old('kind') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="form-row">
                    <label>Nombre completo<input name="name" required maxlength="120" value="<?= old('name') ?>"></label>
                    <label>Correo electrónico <span class="field-hint">(al que responderemos)</span>
                        <input type="email" name="email" required maxlength="190" value="<?= old('email') ?>"></label>
                </div>
                <div class="form-row">
                    <label>Documento de identidad <span class="field-hint">(opcional, ayuda a verificar)</span>
                        <input name="document" maxlength="40" value="<?= old('document') ?>"></label>
                    <label>Institución <span class="field-hint">(si aplica)</span>
                        <input name="institution" maxlength="190" value="<?= old('institution') ?>"></label>
                </div>
                <label>Cuéntanos los detalles <span class="field-hint">(opcional)</span>
                    <textarea name="detail" rows="4" maxlength="3000"><?= old('detail') ?></textarea></label>
                <label class="check"><input type="checkbox" name="titular" value="1" <?= !empty($_SESSION['_old']['titular']) ? 'checked' : '' ?>>
                    <span>Declaro que soy el titular de los datos o su representante legal, y que la información que entrego es verdadera.</span></label>
                <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                <button class="btn btn-primary btn-lg">Enviar solicitud</button>
            </form>

            <p class="muted small mt-24">También puedes escribirnos directamente a
                <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a>.
                Si no quedas conforme con nuestra respuesta, puedes acudir a la Superintendencia de Industria y Comercio.
                Consulta el <a href="<?= e(url('legal.php#privacidad')) ?>">aviso de privacidad</a> para saber qué datos tratamos y por qué.</p>
        </div>
    </div>
</main>
<?php clear_old(); include __DIR__ . '/includes/partials/footer.php'; ?>
