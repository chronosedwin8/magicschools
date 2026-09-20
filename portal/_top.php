<?php
/** @var array $user */
/** @var string $active */
$areaLabel = $areaLabel ?? 'Portal de clientes';
$pageTitle = ($pageTitle ?? 'Portal') . ' | ' . $areaLabel . ' ' . BRAND_SHORT;
$bodyClass = 'portal';
$extraCss = 'assets/css/portal.css';
include __DIR__ . '/../includes/partials/head.php';
$initials = mb_strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice(preg_split('/\s+/', trim($user['name'])), 0, 2))));
$nav = $nav ?? [
    'inicio'   => ['portal/', '🏠', 'Inicio'],
    'licencias'=> ['portal/licencias.php', '🎟️', 'Mis licencias'],
    'pedidos'  => ['portal/pedidos.php', '🧾', 'Pedidos y pagos'],
    'cuenta'   => ['portal/cuenta.php', '👤', 'Mi cuenta'],
];
$isAdminArea = $isAdminArea ?? false;
?>
<div class="portal-shell">
    <aside class="portal-side" id="portal-side">
        <a class="nav-brand" href="<?= e(url('')) ?>"><?php $logoLight = true; include __DIR__ . '/../includes/partials/logo.php'; $logoLight = false; ?></a>
        <span class="side-label"><?= e($areaLabel) ?></span>
        <nav class="side-nav">
            <?php foreach ($nav as $key => [$href, $icon, $label]): ?>
                <a href="<?= e(url($href)) ?>" class="<?= ($active ?? '') === $key ? 'active' : '' ?>"><span><?= $icon ?></span><?= e($label) ?></a>
            <?php endforeach; ?>
            <?php if (is_admin($user)): ?>
                <span class="side-label"><?= $isAdminArea ? 'Portal de clientes' : 'Administración' ?></span>
                <?php if ($isAdminArea): ?>
                    <a href="<?= e(url('portal/')) ?>"><span>🏠</span>Ir al portal</a>
                <?php else: ?>
                    <a href="<?= e(url('admin/')) ?>"><span>🛠️</span>Panel de administración</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
        <?php if (!$isAdminArea): ?>
        <div class="side-cta">
            <strong>¿Necesitas más cupos?</strong>
            <p>Adquiere una licencia adicional para tu institución.</p>
            <a class="btn btn-light btn-sm btn-block" href="<?= e(url('checkout.php')) ?>">Comprar licencia</a>
        </div>
        <?php endif; ?>
        <div class="side-help">
            <small>Soporte</small>
            <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a>
        </div>
    </aside>
    <div class="portal-main">
        <header class="portal-top">
            <button class="side-toggle" aria-label="Menú" onclick="document.body.classList.toggle('side-open')">☰</button>
            <div class="top-institution">
                <small>Institución</small>
                <strong><?= e($user['institution']) ?></strong>
            </div>
            <div class="top-user">
                <span class="avatar"><?= e($initials) ?></span>
                <div class="top-user-info"><strong><?= e($user['name']) ?></strong><small><?= e($user['email']) ?></small></div>
                <a class="btn btn-ghost btn-sm" href="<?= e(url('portal/logout.php')) ?>">Salir</a>
            </div>
        </header>
        <main class="portal-content">
            <?= render_flashes() ?>
