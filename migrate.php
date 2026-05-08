<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';

$pdo = medal_pdo();
if (!$pdo) die("❌ Database connection failed.");

try {
    // 1. Ensure role/permissions columns exist on admin_users
    $pdo->exec("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'superadmin'");
    $pdo->exec("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS permissions TEXT NULL");
    echo "✅ admin_users: role & permissions columns OK<br>";

    // 2. Add stock column to product_variants
    $pdo->exec("ALTER TABLE product_variants ADD COLUMN IF NOT EXISTS stock INT NOT NULL DEFAULT 0");
    echo "✅ product_variants: stock column added<br>";

    // 3. Add file_sharing_url to products if missing
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS file_sharing_url TEXT NULL");
    echo "✅ products: file_sharing_url column OK<br>";

    // 4. Ensure superadmin role for the first admin
    $pdo->exec("UPDATE admin_users SET role = 'superadmin' WHERE id = 1");
    echo "✅ First admin set as superadmin<br>";

    // 5. Create homepage_offers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS homepage_offers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        image_key VARCHAR(128) NOT NULL,
        link_url VARCHAR(255) NULL,
        sort_order INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB");
    echo "✅ homepage_offers: table created OK<br>";

    echo "<br><strong style='color:green'>✅ All migrations completed successfully!</strong>";
    echo "<br><a href='admin/index.php'>Go to Admin Panel →</a>";

} catch (Throwable $e) {
    echo "❌ Migration failed: " . htmlspecialchars($e->getMessage());
}
