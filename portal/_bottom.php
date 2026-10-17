        </main>
        <footer class="portal-foot">© <?= date('Y') ?> <?= e(COMPANY_NAME) ?> · <a href="<?= e(url('')) ?>">Volver al sitio</a></footer>
    </div>
</div>
<div class="side-backdrop" onclick="document.body.classList.remove('side-open')"></div>
<script src="<?= e(url('assets/js/main.js')) ?>?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>" defer></script>
<?php foreach ((array) ($pageJs ?? []) as $script): if ($script === '') continue; ?>
    <script src="<?= e(url($script)) ?>?v=<?= filemtime(__DIR__ . '/../' . $script) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
