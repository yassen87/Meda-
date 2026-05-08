<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_bootstrap.php';

$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
$required = null;

$map = [
    'orders.php' => 'orders',
    'order_view.php' => 'orders',
    'order_management.php' => 'orders',
    'products.php' => 'products',
    'product_edit.php' => 'products',
    'product_save.php' => 'products',
    'internal_products.php' => 'products',
    'categories.php' => 'categories',
    'category_save.php' => 'categories',
    'promo_codes.php' => 'promo_codes',
    'promo_code_save.php' => 'promo_codes',
    'clients.php' => 'clients',
    'client_edit.php' => 'clients',
    'client_save.php' => 'clients',
    'messages.php' => 'messages',
    'settings.php' => 'settings',
    'admins.php' => 'settings',
    'shipping.php' => 'settings',
    'faqs.php' => 'settings',
];

$required = $map[$script] ?? null;

require_admin($required);
