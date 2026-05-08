<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/products.php';

// Route cart actions to checkout page since it's a one-page checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $pid = (int) ($_POST['product_id'] ?? 0);
        $qty = (int) ($_POST['qty'] ?? 1);
        $variantRaw = $_POST['variant_id'] ?? '';
        $variantId = $variantRaw === '' ? null : (int) $variantRaw;
        if ($variantId === 0) {
            $variantId = null;
        }
        if (get_product_by_id($pid) !== null) {
            add_to_cart($pid, $qty, $variantId);
            header('Location: ' . url('checkout.php'));
        } else {
            header('Location: ' . url('products.php'));
        }
        exit;
    }
}

header('Location: ' . url('checkout.php'));
exit;
