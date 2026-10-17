<?php
/**
 * Aviso de cookies. El sitio solo usa cookies necesarias, así que este aviso
 * informa y registra la decisión; no bloquea ninguna función.
 * Si algún día se agregan cookies de analítica o publicidad, este mismo
 * componente debe pasar a pedir consentimiento ANTES de instalarlas.
 */
?>
<div class="cookie-notice" id="cookie-notice" role="region" aria-label="Aviso de cookies" hidden>
    <div class="cookie-text">
        <strong>Usamos solo cookies necesarias</strong>
        <p>Sirven para mantener tu sesión y proteger los formularios. No usamos cookies de analítica ni de publicidad.
            Puedes leer el detalle en la <a href="<?= e(url('legal.php#cookies')) ?>">política de cookies</a>.</p>
    </div>
    <div class="cookie-actions">
        <button type="button" class="btn btn-primary btn-sm" data-cookie-accept>Entendido</button>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('legal.php#cookies')) ?>">Ver política</a>
    </div>
</div>
