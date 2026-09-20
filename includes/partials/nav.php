<?php $navUser = current_user(); $home = url(''); ?>
<div class="announce">
    <span class="announce-pill">Nuevo</span>
    Regreso a clases con IA: adquiere tu licencia institucional y activa a todo tu equipo docente en minutos.
    <a href="<?= e($home) ?>#precios">Ver planes <span aria-hidden="true">→</span></a>
</div>
<header class="site-header" id="top">
    <nav class="nav container">
        <a class="nav-brand" href="<?= e($home) ?>" aria-label="<?= e(BRAND_NAME) ?> inicio">
            <?php include __DIR__ . '/logo.php'; ?>
        </a>
        <button class="nav-toggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="nav-menu">
            <span></span><span></span><span></span>
        </button>
        <div class="nav-menu" id="nav-menu">
            <ul class="nav-links">
                <li class="has-dropdown">
                    <button class="nav-link" aria-expanded="false">Soluciones <svg width="10" height="10" viewBox="0 0 10 10"><path d="M1 3l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></button>
                    <div class="dropdown">
                        <a href="<?= e($home) ?>#soluciones" class="dropdown-item">
                            <span class="dd-icon dd-purple">🏫</span>
                            <span><strong>IA para colegios</strong><small>Seguridad, control y analítica institucional</small></span>
                        </a>
                        <a href="<?= e($home) ?>#soluciones" class="dropdown-item">
                            <span class="dd-icon dd-pink">🍎</span>
                            <span><strong>IA para docentes</strong><small>Más de 80 herramientas para planear y evaluar</small></span>
                        </a>
                        <a href="<?= e($home) ?>#soluciones" class="dropdown-item">
                            <span class="dd-icon dd-orange">🎒</span>
                            <span><strong>IA para estudiantes</strong><small>Experiencias guiadas por el docente</small></span>
                        </a>
                    </div>
                </li>
                <li><a class="nav-link" href="<?= e($home) ?>#herramientas">Herramientas</a></li>
                <li><a class="nav-link" href="<?= e($home) ?>#seguridad">Seguridad</a></li>
                <li><a class="nav-link" href="<?= e($home) ?>#precios">Precios</a></li>
                <li><a class="nav-link" href="<?= e($home) ?>#faq">Preguntas</a></li>
            </ul>
            <div class="nav-actions">
                <?php if ($navUser): ?>
                    <a class="btn btn-ghost" href="<?= e(url(is_admin($navUser) ? 'admin/' : 'portal/')) ?>"><?= is_admin($navUser) ? 'Administración' : 'Mi portal' ?></a>
                <?php else: ?>
                    <a class="btn btn-ghost" href="<?= e(url('portal/login.php')) ?>">Portal de clientes</a>
                <?php endif; ?>
                <a class="btn btn-primary" href="<?= e($home) ?>#precios">Comprar licencia</a>
            </div>
        </div>
    </nav>
</header>
