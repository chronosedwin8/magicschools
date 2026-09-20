<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();
$selected = plan($_POST['plan'] ?? $_GET['plan'] ?? '') ?? $PLANS['escuela'];
$errors = [];

if (is_post()) {
    csrf_check();
    set_old($_POST);

    $selected = plan($_POST['plan'] ?? '');
    if (!$selected) {
        $errors[] = 'Selecciona un plan válido.';
    }

    if (!$user) {
        $name        = trim($_POST['name'] ?? '');
        $email       = mb_strtolower(trim($_POST['email'] ?? ''));
        $password    = (string) ($_POST['password'] ?? '');
        $password2   = (string) ($_POST['password_confirm'] ?? '');
        $institution = trim($_POST['institution'] ?? '');
        $docType     = in_array($_POST['document_type'] ?? '', ['NIT', 'CC', 'CE'], true) ? $_POST['document_type'] : 'NIT';
        $docNumber   = preg_replace('/[^0-9A-Za-z\-]/', '', (string) ($_POST['document_number'] ?? ''));
        $phone       = trim($_POST['phone'] ?? '');
        $city        = trim($_POST['city'] ?? '');

        if ($name === '' || mb_strlen($name) > 120)              $errors[] = 'Escribe el nombre del responsable.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))          $errors[] = 'Escribe un correo electrónico válido.';
        if ($institution === '')                                 $errors[] = 'Escribe el nombre de la institución.';
        if ($docNumber === '' || strlen($docNumber) < 5)          $errors[] = 'Escribe un número de documento o NIT válido.';
        if ($phone === '')                                       $errors[] = 'Escribe un teléfono de contacto.';

        $existing = $email !== '' ? db_one('SELECT * FROM users WHERE email = ?', [$email]) : null;
        if ($existing) {
            // Cliente que ya tiene cuenta: debe usar su contraseña
            if (!password_verify($password, $existing['password_hash'])) {
                $errors[] = 'Ya existe una cuenta con ese correo. Escribe tu contraseña actual o inicia sesión primero.';
            }
        } else {
            if (strlen($password) < 8)       $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
            if ($password !== $password2)    $errors[] = 'Las contraseñas no coinciden.';
        }
        if (empty($_POST['terms'])) {
            $errors[] = 'Debes aceptar los términos y la política de tratamiento de datos.';
        }

        if (!$errors) {
            if ($existing) {
                $user = $existing;
            } else {
                db_exec(
                    'INSERT INTO users (name, email, password_hash, institution, document_type, document_number, phone, city)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [$name, $email, password_hash($password, PASSWORD_DEFAULT), $institution, $docType, $docNumber, $phone, $city]
                );
                $user = db_one('SELECT * FROM users WHERE id = ?', [(int) db()->lastInsertId()]);
            }
            login_user((int) $user['id']);
        }
    } elseif (empty($_POST['terms'])) {
        $errors[] = 'Debes aceptar los términos y la política de tratamiento de datos.';
    }

    if (!$errors) {
        clear_old();
        $order = create_order((int) $user['id'], $selected);

        if (mp_enabled() && MP_CHECKOUT_MODE === 'custom') {
            redirect('pago/pagar.php?ref=' . urlencode($order['reference']));
        } elseif (mp_enabled()) {
            try {
                $pref = mp_create_preference($order, $user, $selected);
                db_exec('UPDATE orders SET mp_preference_id = ?, updated_at = NOW() WHERE id = ?', [$pref['id'], $order['id']]);
                redirect($pref['init_url']);
            } catch (Throwable $e) {
                app_log('mercadopago', 'No se pudo crear la preferencia', ['order' => $order['reference'], 'error' => $e->getMessage()]);
                $errors[] = 'No pudimos iniciar el pago con Mercado Pago. Inténtalo de nuevo en unos minutos.'
                    . (APP_DEBUG ? ' (' . $e->getMessage() . ')' : '');
            }
        } elseif (mp_demo()) {
            redirect('pago/simulador.php?ref=' . urlencode($order['reference']));
        } else {
            $errors[] = 'Los pagos no están configurados todavía. Contáctanos a ' . SUPPORT_EMAIL . '.';
        }
    }
}

$pageTitle = 'Comprar licencia | ' . BRAND_NAME;
$bodyClass = 'page-simple';
include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/nav.php';
?>
<main class="checkout">
    <div class="container">
        <a class="page-back" href="<?= e(url('')) ?>#precios">← Volver a precios</a>
        <div class="checkout-grid">
            <form class="card" method="post" novalidate>
                <?= csrf_field() ?>
                <h2>Completa tu compra</h2>
                <p class="muted">Crea tu cuenta del portal de clientes. Con ella administrarás tu licencia y tus usuarios.</p>

                <?php if (mp_demo()): ?>
                    <div class="demo-banner"><strong>Modo demostración:</strong> aún no hay credenciales de Mercado Pago configuradas; el pago se simulará localmente.</div>
                <?php endif; ?>
                <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
                <?= render_flashes() ?>

                <p class="card-title-sm">1. Elige tu licencia</p>
                <div class="plan-switch">
                    <?php foreach ($PLANS as $p): ?>
                        <label class="plan-option">
                            <input type="radio" name="plan" value="<?= e($p['code']) ?>"
                                data-name="<?= e($p['name']) ?>" data-price="<?= e(money($p['price'])) ?>"
                                data-seats="<?= (int) $p['seats'] ?>" data-months="<?= (int) $p['months'] ?>"
                                <?= $selected['code'] === $p['code'] ? 'checked' : '' ?>>
                            <strong><?= e($p['name']) ?></strong>
                            <span><?= money_short($p['price']) ?> · <?= (int) $p['seats'] ?> usuarios</span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <?php if ($user): ?>
                    <p class="card-title-sm">2. Datos de la cuenta</p>
                    <div class="alert alert-info">Comprarás como <strong><?= e($user['name']) ?></strong> (<?= e($user['email']) ?>) para <strong><?= e($user['institution']) ?></strong>. ¿No eres tú? <a href="<?= e(url('portal/logout.php')) ?>">Cerrar sesión</a></div>
                <?php else: ?>
                    <p class="card-title-sm">2. Datos de la institución</p>
                    <label>Nombre de la institución<input name="institution" value="<?= old('institution') ?>" required maxlength="190" placeholder="Ej. Colegio San José"></label>
                    <div class="form-row">
                        <label>Tipo de documento
                            <select name="document_type">
                                <?php foreach (['NIT' => 'NIT', 'CC' => 'Cédula de ciudadanía', 'CE' => 'Cédula de extranjería'] as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= old('document_type', 'NIT') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Número de documento<input name="document_number" value="<?= old('document_number') ?>" required maxlength="30" placeholder="900123456-7"></label>
                    </div>
                    <div class="form-row">
                        <label>Ciudad<input name="city" value="<?= old('city') ?>" maxlength="80" placeholder="Bogotá"></label>
                        <label>Teléfono<input name="phone" value="<?= old('phone') ?>" required maxlength="30" placeholder="300 000 0000"></label>
                    </div>

                    <p class="card-title-sm">3. Administrador de la licencia</p>
                    <div class="form-row">
                        <label>Nombre completo<input name="name" value="<?= old('name') ?>" required maxlength="120" autocomplete="name"></label>
                        <label>Correo electrónico<input type="email" name="email" value="<?= old('email') ?>" required maxlength="190" autocomplete="email"></label>
                    </div>
                    <div class="form-row">
                        <label>Contraseña <span class="field-hint">(mín. 8 caracteres)</span><input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
                        <label>Confirmar contraseña<input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"></label>
                    </div>
                    <p class="muted small">¿Ya tienes cuenta? Usa tu correo y contraseña actuales o <a href="<?= e(url('portal/login.php')) ?>">inicia sesión</a>.</p>
                <?php endif; ?>

                <label class="check"><input type="checkbox" name="terms" value="1" <?= !empty($_SESSION['_old']['terms']) ? 'checked' : '' ?>>
                    <span>Acepto los <a href="<?= e(url('legal.php#terminos')) ?>" target="_blank">términos del servicio</a> y la <a href="<?= e(url('legal.php#privacidad')) ?>" target="_blank">política de tratamiento de datos personales</a>.</span></label>

                <button class="btn btn-primary btn-lg btn-block">Continuar al pago →</button>
                <div class="secure-note">🔒 En el siguiente paso eliges cómo pagar: tarjeta de crédito o débito, PSE o Efecty. Pago cifrado y seguro.</div>
            </form>

            <aside class="card summary">
                <span class="plan-pill">Resumen del pedido</span>
                <h2 data-sum="name"><?= e($selected['name']) ?></h2>
                <div class="summary-row"><span>Usuarios incluidos</span><strong data-sum="seats"><?= (int) $selected['seats'] ?></strong></div>
                <div class="summary-row"><span>Vigencia</span><strong><span data-sum="months"><?= (int) $selected['months'] ?></span> meses</strong></div>
                <div class="summary-row"><span>Activación</span><strong>Inmediata</strong></div>
                <div class="summary-row total"><span>Total</span><span data-sum="price"><?= e(money($selected['price'])) ?></span></div>
                <ul class="checklist">
                    <li>Acceso inmediato al portal de clientes</li>
                    <li>Agrega y administra tus usuarios</li>
                    <li>Comprobante de compra disponible en tu portal</li>
                </ul>
                <?php $noticeCompact = true; include __DIR__ . '/includes/partials/commerce_notice.php'; ?>
            </aside>
        </div>
    </div>
</main>
<?php include __DIR__ . '/includes/partials/footer.php'; ?>
