<?php
require_once __DIR__ . '/includes/db.php';
$pdo = medal_pdo();
if ($pdo) {
    try {
        $pdo->exec("ALTER TABLE product_variants ADD COLUMN stock INT NOT NULL DEFAULT 0 AFTER compare_at_price");
        echo "Successfully added stock column to product_variants table.";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Could not connect to database.";
}
