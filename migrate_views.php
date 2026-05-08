<?php
$pdo = new PDO('mysql:host=localhost;dbname=medal_db;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
try {
    $pdo->exec('ALTER TABLE products ADD COLUMN view_count INT UNSIGNED NOT NULL DEFAULT 0');
    echo "Added view_count column.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "view_count already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
