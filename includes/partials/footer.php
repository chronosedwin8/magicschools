<?php $home = url(''); ?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <?php $logoLight = true; include __DIR__ . '/logo.php'; $logoLight = false; ?>
            <p>Inteligencia artificial segura y responsable para que cada docente recupere tiempo y cada estudiante aprenda mejor.</p>
            <div class="socials">
                <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24"><path d="M14 8h3V4h-3c-2.8 0-5 2.2-5 5v2H7v4h2v9h4v-9h3l1-4h-4V9c0-.6.4-1 1-1z"/></svg></a>
                <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24"><path d="M12 7a5 5 0 100 10 5 5 0 000-10zm0 8.2a3.2 3.2 0 110-6.4 3.2 3.2 0 010 6.4zM17.3 5.5a1.2 1.2 0 100 2.4 1.2 1.2 0 000-2.4zM12 2c-2.7 0-3 0-4.1.1C4.3 2.3 2.3 4.3 2.1 7.9 2 9 2 9.3 2 12s0 3 .1 4.1c.2 3.6 2.2 5.6 5.8 5.8 1.1.1 1.4.1 4.1.1s3 0 4.1-.1c3.6-.2 5.6-2.2 5.8-5.8.1-1.1.1-1.4.1-4.1s0-3-.1-4.1c-.2-3.6-2.2-5.6-5.8-5.8C15 2 14.7 2 12 2z"/></svg></a>
                <a href="#" aria-label="X"><svg viewBox="0 0 24 24"><path d="M17.8 3h3.1l-6.8 7.8L22 21h-6.2l-4.9-6.4L5.3 21H2.2l7.3-8.3L2 3h6.4l4.4 5.8L17.8 3zm-1.1 16.2h1.7L7.4 4.7H5.6l11.1 14.5z"/></svg></a>
                <a href="#" aria-label="YouTube"><svg viewBox="0 0 24 24"><path d="M23 7.2a3 3 0 00-2.1-2.1C19 4.6 12 4.6 12 4.6s-7 0-8.9.5A3 3 0 001 7.2 31 31 0 00.5 12a31 31 0 00.5 4.8 3 3 0 002.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 002.1-2.1 31 31 0 00.5-4.8 31 31 0 00-.5-4.8zM9.8 15V9l5.8 3-5.8 3z"/></svg></a>
                <a href="#" aria-label="LinkedIn"><svg viewBox="0 0 24 24"><path d="M4.98 3.5a2.5 2.5 0 110 5 2.5 2.5 0 010-5zM3 9.5h4V21H3zM9.5 9.5h3.8v1.6h.1c.5-1 1.8-2 3.8-2 4 0 4.8 2.6 4.8 6V21h-4v-5.2c0-1.2 0-2.8-1.7-2.8s-2 1.3-2 2.7V21h-4z"/></svg></a>
            </div>
        </div>
        <div>
            <h4>Soluciones</h4>
            <ul>
                <li><a href="<?= e($home) ?>#soluciones">IA para colegios</a></li>
                <li><a href="<?= e($home) ?>#soluciones">IA para docentes</a></li>
                <li><a href="<?= e($home) ?>#soluciones">IA para estudiantes</a></li>
                <li><a href="<?= e($home) ?>#herramientas">Herramientas</a></li>
                <li><a href="<?= e($home) ?>#integraciones">Integraciones</a></li>
            </ul>
        </div>
        <div>
            <h4>Recursos</h4>
            <ul>
                <li><a href="<?= e($home) ?>#comunidad">Formación y certificación</a></li>
                <li><a href="<?= e($home) ?>#comunidad">Comunidad de pioneros</a></li>
                <li><a href="<?= e($home) ?>#faq">Preguntas frecuentes</a></li>
                <li><a href="<?= e(url('portal/login.php')) ?>">Portal de clientes</a></li>
            </ul>
        </div>
        <div>
            <h4>Compañía</h4>
            <ul>
                <li><a href="<?= e($home) ?>#precios">Precios</a></li>
                <li><a href="<?= e($home) ?>#contacto">Contacto</a></li>
                <li><a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a></li>
                <li><?= e(SUPPORT_PHONE) ?></li>
            </ul>
        </div>
    </div>
    <div class="container footer-bottom">
        <?php
        // Solo se muestran los datos que estén completos en config.php
        $footerBits = array_filter([
            '© ' . date('Y') . ' ' . COMPANY_NAME,
            COMPANY_NIF !== '' ? 'NIF ' . COMPANY_NIF : '',
            COMPANY_ADDRESS,
            COMPANY_REGISTRY,
        ], 'strlen');
        ?>
        <span><?= e(rtrim(implode(' · ', $footerBits), '.')) ?>. Comercio electrónico desde <?= e(COMPANY_COUNTRY) ?>.</span>
        <span class="footer-legal">
            <a href="<?= e(url('legal.php#aviso')) ?>">Aviso legal</a>
            <a href="<?= e(url('legal.php#privacidad')) ?>">Privacidad</a>
            <a href="<?= e(url('legal.php#terminos')) ?>">Términos</a>
            <a href="<?= e($home) ?>#seguridad">Centro de confianza</a>
            <a href="<?= e(url('legal.php#accesibilidad')) ?>">Accesibilidad</a>
        </span>
    </div>
</footer>
<script src="<?= e(url('assets/js/main.js')) ?>?v=<?= filemtime(__DIR__ . '/../../assets/js/main.js') ?>" defer></script>
</body>
</html>
