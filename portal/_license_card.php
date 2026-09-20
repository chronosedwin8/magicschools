<?php
/** @var array $l */
[$stLabel, $stClass] = license_status_label($l);
$pct = $l['seats'] > 0 ? min(100, round($l['used_seats'] / $l['seats'] * 100)) : 0;
?>
<article class="license-card <?= $l['plan_code'] === 'volumen' ? 'lc-volumen' : 'lc-escuela' ?>">
    <div class="lc-head">
        <div>
            <span class="lc-plan"><?= e($l['plan_name']) ?></span>
            <div class="lc-key"><code><?= e($l['license_key']) ?></code>
                <button type="button" class="copy-btn" data-copy="<?= e($l['license_key']) ?>">Copiar</button></div>
        </div>
        <span class="badge badge-<?= e($stClass) ?>"><?= e($stLabel) ?></span>
    </div>
    <div class="lc-seats">
        <div class="lc-seats-row"><span><strong><?= (int) $l['used_seats'] ?></strong> de <?= (int) $l['seats'] ?> usuarios</span><span><?= $pct ?>%</span></div>
        <div class="progress"><span style="width: <?= $pct ?>%"></span></div>
    </div>
    <dl class="lc-meta">
        <div><dt>Inicio</dt><dd><?= fecha($l['starts_at']) ?></dd></div>
        <div><dt>Vence</dt><dd><?= fecha($l['expires_at']) ?></dd></div>
        <div><dt>Orden</dt><dd><?= e($l['reference']) ?></dd></div>
        <div><dt>Valor</dt><dd><?= e(money($l['amount'])) ?></dd></div>
    </dl>
    <a class="btn btn-primary btn-block" href="<?= e(url('portal/licencia.php?id=' . $l['id'])) ?>">Administrar usuarios →</a>
</article>
