<?php
/**
 * Activación de la cuenta del docente.
 * Entra con el correo y el código que le entregó su institución, define su
 * contraseña y queda vinculado a la licencia.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

if (current_user()) {
    redirect('portal/');
}

$errors = [];
if (is_post()) {
    csrf_check();
    set_old($_POST);

    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($_POST['code'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');

    // Límite de intentos por sesión, como en el inicio de sesión
    $_SESSION['_activate_attempts'] = array_filter($_SESSION['_activate_attempts'] ?? [], fn($t) => $t > time() - 900);
    if (count($_SESSION['_activate_attempts']) >= 8) {
        $errors[] = 'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.';
    }

    if (!$errors) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Escribe el correo con el que te registró tu institución.';
        if ($code === '')                               $errors[] = 'Escribe el código de activación.';
        if (strlen($password) < 8)                      $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ($password !== $confirm)                     $errors[] = 'Las contraseñas no coinciden.';
        if (empty($_POST['terms']))                     $errors[] = 'Debes aceptar los términos y el aviso de privacidad.';
    }

    if (!$errors) {
        $member = db_one('SELECT * FROM license_members WHERE email = ? AND access_code = ?', [$email, $code]);
        if (!$member) {
            $_SESSION['_activate_attempts'][] = time();
            $errors[] = 'El correo y el código no coinciden. Pídele a quien administra la licencia que los verifique.';
        } elseif ($member['user_id'] !== null) {
            $errors[] = 'Esta cuenta ya está activada. Inicia sesión con tu contraseña o escríbenos si la olvidaste.';
        } else {
            $license = db_one('SELECT * FROM licenses WHERE id = ?', [$member['license_id']]);
            if (!$license || !license_is_usable($license)) {
                $errors[] = 'La licencia de tu institución no está activa. Avisa a quien la administra.';
            } else {
                $owner = db_one('SELECT institution FROM users WHERE id = ?', [$license['user_id']]);
                $existing = db_one('SELECT * FROM users WHERE email = ?', [$email]);

                $pdo = db();
                $pdo->beginTransaction();
                try {
                    if ($existing) {
                        // Ya tenía cuenta (por ejemplo, es también quien compró): solo se vincula.
                        $userId = (int) $existing['id'];
                    } else {
                        db_exec(
                            'INSERT INTO users (name, email, password_hash, role, institution, document_type, document_number)
                             VALUES (?, ?, ?, "teacher", ?, "CC", "-")',
                            [mb_substr($member['name'], 0, 120), $email, password_hash($password, PASSWORD_DEFAULT),
                             mb_substr((string) ($owner['institution'] ?? ''), 0, 190)]
                        );
                        $userId = (int) $pdo->lastInsertId();
                    }
                    db_exec('UPDATE license_members SET user_id = ?, activated_at = NOW() WHERE id = ? AND user_id IS NULL',
                        [$userId, $member['id']]);
                    $pdo->commit();
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $e;
                }

                app_log('docentes', 'Cuenta activada', ['licencia' => $license['id'], 'miembro' => $member['id']]);
                clear_old();
                login_user($userId);
                flash('success', '¡Listo! Tu cuenta quedó activada. Estas son tus herramientas.');
                redirect('portal/herramientas.php');
            }
        }
    }
}

$pageTitle = 'Activar mi cuenta de docente';
$bodyClass = 'auth-page';
$extraCss = 'assets/css/portal.css';
include __DIR__ . '/../includes/partials/head.php';
?>
<main class="auth">
    <section class="auth-art">
        <a href="<?= e(url('')) ?>"><?php $logoLight = true; include __DIR__ . '/../includes/partials/logo.php'; ?></a>
        <div>
            <h1>Activa tu cuenta y <span class="text-gradient-light">empieza a crear</span></h1>
            <p>Tu institución te registró en su licencia. Con tu correo y el código de activación entras a todas las herramientas.</p>
            <ul class="auth-points">
                <li>Planes de clase, evaluaciones y materiales en minutos</li>
                <li>Descarga en PDF, Word, PowerPoint y HTML</li>
                <li>Tus documentos quedan guardados en tu biblioteca</li>
            </ul>
        </div>
        <small>© <?= date('Y') ?> <?= e(COMPANY_NAME) ?></small>
    </section>
    <section class="auth-form-wrap">
        <form class="auth-form" method="post" novalidate>
            <?= csrf_field() ?>
            <h2>Activar mi cuenta</h2>
            <p class="muted">¿Ya la activaste? <a href="<?= e(url('portal/login.php')) ?>">Inicia sesión</a>.</p>
            <?= render_flashes() ?>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>

            <label>Correo institucional<input type="email" name="email" required maxlength="190" value="<?= old('email') ?>" autocomplete="email" autofocus></label>
            <label>Código de activación <span class="field-hint">(8 caracteres, te lo da tu institución)</span>
                <input name="code" required maxlength="20" value="<?= old('code') ?>" style="text-transform:uppercase" placeholder="A1B2C3D4"></label>
            <div class="form-row">
                <label>Crea tu contraseña <span class="field-hint">(mín. 8)</span><input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
                <label>Confírmala<input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"></label>
            </div>
            <label class="check"><input type="checkbox" name="terms" value="1" <?= !empty($_SESSION['_old']['terms']) ? 'checked' : '' ?>>
                <span>Acepto los <a href="<?= e(url('legal.php#registro')) ?>" target="_blank">términos de registro</a> y el
                    <a href="<?= e(url('legal.php#privacidad')) ?>" target="_blank">aviso de privacidad</a>.</span></label>
            <button class="btn btn-primary btn-lg btn-block">Activar y entrar</button>
            <p class="muted small center mt-24">¿No tienes el código? Pídeselo a quien administra la licencia en tu institución.</p>
        </form>
    </section>
</main>
<script src="<?= e(url('assets/js/main.js')) ?>" defer></script>
</body>
</html>
<?php clear_old(); ?>
