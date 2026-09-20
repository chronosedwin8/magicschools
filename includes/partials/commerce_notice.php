<?php
/**
 * Aviso de comercio electrónico internacional.
 * Se muestra en todos los puntos donde el cliente paga.
 * $noticeCompact = true muestra la versión corta (resúmenes de pedido).
 */
$noticeCompact = $noticeCompact ?? false;
?>
<div class="commerce-notice<?= $noticeCompact ? ' compact' : '' ?>">
    <strong>🌍 Compra internacional · Comercio electrónico desde <?= e(COMPANY_COUNTRY) ?></strong>
    <p>
        <?= e(COMPANY_NAME) ?> es una empresa establecida en <?= e(COMPANY_COUNTRY) ?> que presta sus servicios en línea,
        conforme a la Ley 34/2002 de Servicios de la Sociedad de la Información y de Comercio Electrónico (LSSI-CE).
        <?php if (!$noticeCompact): ?>
            El precio que ves es el valor total que cobramos: no añadimos cargos adicionales al finalizar la compra.
            Los tributos o retenciones que, en su caso, correspondan en el país del comprador se rigen por la legislación de ese país.
            Pagos procesados de forma segura por Mercado Pago.
        <?php else: ?>
            El precio mostrado es el valor total; no añadimos cargos adicionales.
        <?php endif; ?>
        <a href="<?= e(url('legal.php#comercio')) ?>" target="_blank" rel="noopener">Condiciones de compra</a>
    </p>
</div>
