<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';

$pdo = medal_pdo();
if (!$pdo) {
    die("No database connection.\n");
}

try {
    // 1. Create settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(64) NOT NULL UNIQUE,
        setting_value_en TEXT,
        setting_value_ar TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "Created table 'settings'.\n";

    // 2. Insert announce_shipping initial values
    $st = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value_en, setting_value_ar) VALUES (?, ?, ?)");
    $st->execute([
        'announce_shipping',
        'Free shipping on orders over 2,000 EGP',
        'شحن مجاني للطلبات فوق 2000 ج.م.'
    ]);
    echo "Initialized 'announce_shipping' setting.\n";

    echo "Database setup successful!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
