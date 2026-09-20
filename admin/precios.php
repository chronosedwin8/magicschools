<?php
require_once __DIR__ . '/_init.php';

$formErrors = [];
if (is_post()) {
    csrf_check();
    $code = (string) ($_POST['code'] ?? '');
    if (($_POST['action'] ?? '') === 'reset') {
        if (isset(default_plans()[$code])) {
            reset_plan($code, (int) $user['id']);
            flash('success', 'Se restauraron los valores originales del plan.');
        }
        redirect('admin/precios.php');
    }
    $formErrors[$code] = save_plan($code, $_POST, (int) $user['id']);
    if (!$formErrors[$code]) {
        flash('success', 'Plan actualizado. El nuevo precio ya se muestra en la página y aplica a las compras nuevas.');
        redirect('admin/precios.php');
    }
}

$defaults = default_plans();
$customized = array_column(db_all('SELECT code, updated_at, updated_by FROM plan_settings'), null, 'code');
$pendingByPlan = array_column(db_all("SELECT plan_code, COUNT(*) n FROM orders WHERE status IN ('pending','review') GROUP BY plan_code"), 'n', 'plan_code');

$active = 'precios';
$pageTitle = 'Precios y planes';
include __DIR__ . '/../portal/_top.php';
?>
<div class="page-head">
    <div>
        <h1>Precios y planes</h1>
        <p class="muted">Los cambios se publican de inmediato en la página de precios, el checkout y la pantalla de cobro.</p>
    </div>
    <a class="btn btn-outline" href="<?= e(url('')) ?>#precios" target="_blank" rel="noopener">Ver precios en el sitio ↗</a>
</div>

<div class="alert alert-info">Un cambio de precio solo aplica a las <strong>compras nuevas</strong>. Los pedidos ya creados conservan el valor con el que se generaron, y las licencias ya vendidas no cambian.</div>

<div class="plans-admin">
    <?php foreach ($PLANS as $code => $p):
        $d = $defaults[$code];
        $isCustom = isset($customized[$code]);
        $errs = $formErrors[$code] ?? [];
        // Si hubo errores se muestran los valores que envió el administrador
        $v = $errs ? array_merge($p, [
            'name' => $_POST['name'] ?? '', 'tagline' => $_POST['tagline'] ?? '',
            'price' => (int) preg_replace('/\D/', '', (string) ($_POST['price'] ?? '')),
            'seats' => (int) ($_POST['seats'] ?? 0), 'months' => (int) ($_POST['months'] ?? 0),
            'featured' => !empty($_POST['featured']),
            'features' => preg_split('/\r\n|\r|\n/', (string) ($_POST['features'] ?? '')),
        ]) : $p;
        $efecty = mp_enabled() ? mp_method_allows('efecty', (float) $v['price']) : null;
    ?>
    <form class="panel plan-edit" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="code" value="<?= e($code) ?>">
        <div class="panel-head">
            <h2><?= e($p['name']) ?> <small class="muted">(<?= e($code) ?>)</small></h2>
            <span class="badge <?= $isCustom ? 'badge-warning' : 'badge-muted' ?>">
                <?= $isCustom ? 'Editado ' . e(fecha($customized[$code]['updated_at'], true)) : 'Valores por defecto' ?>
            </span>
        </div>
        <?php foreach ($errs as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>

        <div class="price-input">
            <label>Precio (COP)
                <div class="money-field"><span>$</span>
                    <input name="price" inputmode="numeric" required value="<?= e(number_format((int) $v['price'], 0, ',', '.')) ?>" data-price data-seats-target="seats-<?= e($code) ?>" data-per-user="per-<?= e($code) ?>">
                </div>
            </label>
            <div class="price-meta">
                <span>Por usuario: <strong id="per-<?= e($code) ?>"><?= e(money_short($v['seats'] > 0 ? round($v['price'] / $v['seats']) : 0)) ?></strong></span>
                <span>Precio original: <?= e(money_short($d['price'])) ?></span>
                <?php if ($efecty !== null): ?>
                    <span><?= $efecty ? '✅ Admite Efecty' : '⚠️ Supera el máximo de Efecty (solo tarjeta y PSE)' ?></span>
                <?php endif; ?>
                <?php if (!empty($pendingByPlan[$code])): ?>
                    <span>⏳ <?= (int) $pendingByPlan[$code] ?> pedido(s) pendiente(s) con el precio anterior</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <label>Nombre del plan<input name="name" required maxlength="100" value="<?= e($v['name']) ?>"></label>
            <label>Descripción corta<input name="tagline" maxlength="200" value="<?= e($v['tagline']) ?>"></label>
        </div>
        <div class="form-row">
            <label>Usuarios incluidos<input type="number" name="seats" id="seats-<?= e($code) ?>" min="1" max="100000" required value="<?= (int) $v['seats'] ?>"></label>
            <label>Vigencia (meses)<input type="number" name="months" min="1" max="60" required value="<?= (int) $v['months'] ?>"></label>
        </div>
        <label>Características <span class="field-hint">(una por línea; se muestran en la tarjeta de precios)</span>
            <textarea name="features" rows="7"><?= e(implode("\n", $v['features'])) ?></textarea>
        </label>
        <label class="check"><input type="checkbox" name="featured" value="1" <?= $v['featured'] ? 'checked' : '' ?>>
            <span>Destacar este plan (tarjeta oscura con la etiqueta “Mejor valor por usuario”)</span></label>

        <div class="plan-actions">
            <button class="btn btn-primary">Guardar cambios</button>
            <?php if ($isCustom): ?>
                <button class="btn btn-ghost" name="action" value="reset" formnovalidate
                    onclick="return confirm('¿Restaurar los valores originales de <?= e($d['name']) ?> (<?= e(money_short($d['price'])) ?>)?')">Restaurar valores originales</button>
            <?php endif; ?>
        </div>
    </form>
    <?php endforeach; ?>
</div>

<script>
document.querySelectorAll('[data-price]').forEach(function (input) {
    var seats = document.getElementById(input.getAttribute('data-seats-target'));
    var per = document.getElementById(input.getAttribute('data-per-user'));
    function fmt(n) { return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
    function update() {
        var digits = input.value.replace(/\D/g, '');
        input.value = digits ? fmt(parseInt(digits, 10)) : '';
        var s = parseInt(seats.value, 10) || 0;
        per.textContent = '$' + (s > 0 && digits ? fmt(Math.round(parseInt(digits, 10) / s)) : '0');
    }
    input.addEventListener('input', update);
    seats.addEventListener('input', update);
});
</script>
<?php include __DIR__ . '/../portal/_bottom.php'; ?>
