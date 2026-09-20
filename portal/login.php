<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (current_user()) {
    redirect('portal/');
}

$error = null;
if (is_post()) {
    csrf_check();
    $email = (string) ($_POST['email'] ?? '');

    // Limitar intentos por sesión
    $_SESSION['_login_attempts'] = array_filter($_SESSION['_login_attempts'] ?? [], fn($t) => $t > time() - 900);
    if (count($_SESSION['_login_attempts']) >= 8) {
        $error = 'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.';
    } elseif ($u = attempt_login($email, (string) ($_POST['password'] ?? ''))) {
        unset($_SESSION['_login_attempts']);
        login_user((int) $u['id']);
        $to = $_SESSION['_intended'] ?? '';
        unset($_SESSION['_intended']);
        // Solo redirecciones internas
        $isInternal = $to !== '' && (str_starts_with($to, base_url() . '/') || (str_starts_with($to, '/') && !str_starts_with($to, '//')));
        redirect($isInternal ? $to : (is_admin($u) ? 'admin/' : 'portal/'));
    } else {
        $_SESSION['_login_attempts'][] = time();
        $error = 'Correo o contraseña incorrectos.';
    }
}

$pageTitle = 'Iniciar sesión | Portal de clientes';
$bodyClass = 'auth-page';
$extraCss = 'assets/css/portal.css';
include __DIR__ . '/../includes/partials/head.php';
?>
<main class="auth">
    <section class="auth-art">
        <a href="<?= e(url('')) ?>"><?php $logoLight = true; include __DIR__ . '/../includes/partials/logo.php'; ?></a>
        <div>
            <h1>Tu licencia, tus docentes, <span class="text-gradient-light">todo en un lugar</span></h1>
            <p>Consulta tus licencias, revisa tus pagos y administra a los usuarios de tu institución.</p>
            <ul class="auth-points">
                <li>🎟️ Código y vigencia de cada licencia</li>
                <li>👩‍🏫 Agrega y retira usuarios cuando quieras</li>
                <li>🧾 Historial de pedidos y pagos</li>
            </ul>
        </div>
        <small>© <?= date('Y') ?> <?= e(COMPANY_NAME) ?></small>
    </section>
    <section class="auth-form-wrap">
        <form class="auth-form" method="post" novalidate>
            <?= csrf_field() ?>
            <h2>Portal de clientes</h2>
            <p class="muted">Inicia sesión con el correo que usaste al comprar tu licencia.</p>
            <?= render_flashes() ?>
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
            <label>Correo electrónico<input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autocomplete="email" autofocus></label>
            <label>Contraseña<input type="password" name="password" required autocomplete="current-password"></label>
            <button class="btn btn-primary btn-lg btn-block">Ingresar</button>
            <p class="muted small center mt-24">¿Olvidaste tu contraseña? Escríbenos a <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a></p>
            <p class="center small">¿Aún no tienes licencia? <a href="<?= e(url('')) ?>#precios">Ver planes</a></p>
        </form>
    </section>
</main>
<script src="<?= e(url('assets/js/main.js')) ?>" defer></script>
</body>
</html>
