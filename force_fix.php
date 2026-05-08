<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';
$pdo = medal_pdo();
echo "<h1>Database Fixer</h1>";
if (!$pdo) {
    die("<p style='color:red'>Database connection failed!</p>");
}
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS homepage_offers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        image_key VARCHAR(128) NOT NULL,
        link_url VARCHAR(255) NULL,
        sort_order INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB");
    echo "<p style='color:green'>Success! Table 'homepage_offers' created or already exists.</p>";
    echo "<p><a href='admin/offers.php'>Go back to Offers Management</a></p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
