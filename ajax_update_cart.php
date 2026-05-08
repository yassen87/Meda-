<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$action = (string) ($_POST['action'] ?? 'update_qty');
$lineKey = (string) ($_POST['line_key'] ?? '');
$promoCode = strtoupper(trim((string) ($_POST['promo_code'] ?? '')));

if ($lineKey === '') {
    echo json_encode(['error' => 'Missing line key']);
    exit;
}

if ($action === 'remove') {
    if (isset($_SESSION['cart'][$lineKey])) {
        unset($_SESSION['cart'][$lineKey]);
    }
} elseif ($action === 'update_qty') {
    $qty = (int) ($_POST['qty'] ?? 1);
    if ($qty < 1) {
        unset($_SESSION['cart'][$lineKey]);
    } else {
        $_SESSION['cart'][$lineKey] = $qty;
    }
}

// Calculate New Totals
$subtotal = 0.0;
$cartCount = 0;
$currentLineTotal = 0.0;
$currentQty = 0;

foreach ($_SESSION['cart'] ?? [] as $lk => $q) {
    if ($q < 1) continue;
    $parsed = cart_parse_line_key((string) $lk);
    $p = get_cart_line_product($parsed['product_id'], $parsed['variant_id']);
    if ($p !== null) {
        $itemTotal = $p['price'] * $q;
        $subtotal += $itemTotal;
        $cartCount += $q;
        if ($lk === $lineKey) {
            $currentLineTotal = $itemTotal;
            $currentQty = $q;
        }
    }
}

// Discount Logic
$discountPercentage = 0;
$pdo = medal_pdo();
if ($promoCode !== '' && $pdo !== null) {
    $promoSt = $pdo->prepare('SELECT discount_percentage, usage_limit, used_count FROM promo_codes WHERE code = ? AND active = 1');
    $promoSt->execute([$promoCode]);
    $promoRow = $promoSt->fetch();
    if ($promoRow) {
        if ($promoRow['usage_limit'] == 0 || $promoRow['used_count'] < $promoRow['usage_limit']) {
            $discountPercentage = (int) $promoRow['discount_percentage'];
        }
    }
}

echo json_encode([
    'success' => true,
    'new_qty' => $currentQty,
    'line_total' => format_price($currentLineTotal),
    'subtotal' => $subtotal,
    'subtotal_formatted' => format_price($subtotal),
    'discount_percentage' => $discountPercentage,
    'cart_count' => $cartCount
]);
