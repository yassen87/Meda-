<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

// Add file_sharing_url column to products table
$pdo = medal_pdo();
if ($pdo !== null) {
    try {
        // Check if column already exists
        $check = $pdo->prepare("SHOW COLUMNS FROM products LIKE 'file_sharing_url'");
        $check->execute();
        if ($check->fetch() === false) {
            // Add the column
            $alter = $pdo->prepare("ALTER TABLE products ADD COLUMN file_sharing_url TEXT NULL AFTER description_ar");
            $alter->execute();
            echo "File sharing URL column added successfully!\n";
        } else {
            echo "File sharing URL column already exists.\n";
        }
    } catch (Throwable $e) {
        echo "Error adding file sharing URL column: " . $e->getMessage() . "\n";
    }
} else {
    echo "Database connection failed.\n";
}
