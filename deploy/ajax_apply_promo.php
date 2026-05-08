<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$code = strtoupper(trim((string) ($_POST['code'] ?? '')));

if ($code === '') {
    echo json_encode(['error' => t('checkout_err_promo_invalid')]);
    exit;
}

$pdo = medal_pdo();
if ($pdo === null) {
    echo json_encode(['error' => 'Database error']);
    exit;
}

try {
    $st = $pdo->prepare('SELECT id, discount_percentage, usage_limit, used_count, active FROM promo_codes WHERE code = ?');
    $st->execute([$code]);
    $promo = $st->fetch();

    if (!$promo || empty($promo['active'])) {
        echo json_encode(['error' => t('checkout_err_promo_invalid')]);
        exit;
    }

    if ($promo['usage_limit'] > 0 && $promo['used_count'] >= $promo['usage_limit']) {
        echo json_encode(['error' => t('checkout_err_promo_limit')]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'discount_percentage' => (int) $promo['discount_percentage']
    ]);
} catch (\Exception $e) {
    echo json_encode(['error' => 'An error occurred']);
}
