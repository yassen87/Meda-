</main>
</div>
<script src="<?= esc(admin_asset('assets/js/admin-theme.js?v=1.1')) ?>" defer></script>
<?php if (strpos($_SERVER['PHP_SELF'], 'product_edit.php') !== false): ?>
<script src="<?= esc(admin_asset('../assets/js/image-upload.js?v=1.0')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
