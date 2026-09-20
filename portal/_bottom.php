        </main>
        <footer class="portal-foot">© <?= date('Y') ?> <?= e(COMPANY_NAME) ?> · <a href="<?= e(url('')) ?>">Volver al sitio</a></footer>
    </div>
</div>
<div class="side-backdrop" onclick="document.body.classList.remove('side-open')"></div>
<script src="<?= e(url('assets/js/main.js')) ?>?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>" defer></script>
</body>
</html>
