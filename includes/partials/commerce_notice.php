<?php
/**
 * Aviso de condiciones de compra.
 * Se muestra en todos los puntos donde el cliente paga.
 * $noticeCompact = true muestra la versión corta (resúmenes de pedido).
 */
$noticeCompact = $noticeCompact ?? false;
?>
<div class="commerce-notice<?= $noticeCompact ? ' compact' : '' ?>">
    <strong>🌍 Venta en línea desde <?= e(COMPANY_COUNTRY) ?></strong>
    <p>
        Vendedor: <?= e(COMPANY_NAME) ?><?= COMPANY_TAX_ID !== '' ? ' (' . e(tax_label()) . ' ' . e(COMPANY_TAX_ID) . ')' : '' ?>.
        <?php if (!$noticeCompact): ?>
            El precio que ves es el valor total que cobramos: no añadimos cargos adicionales al finalizar la compra.
            Los tributos o retenciones que, en su caso, correspondan en el país del comprador se rigen por la legislación de ese país.
            El pago se procesa de forma segura a través de la pasarela habilitada; no almacenamos datos de tarjetas.
        <?php else: ?>
            El precio mostrado es el valor total; no añadimos cargos adicionales.
        <?php endif; ?>
        <a href="<?= e(url('legal.php#comercio')) ?>" target="_blank" rel="noopener">Condiciones de compra</a>
    </p>
</div>
