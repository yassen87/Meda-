<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

// Add khinat category
$pdo = medal_pdo();
if ($pdo !== null) {
    try {
        // Check if khinat category already exists
        $check = $pdo->prepare("SELECT id FROM categories WHERE slug = 'khinat'");
        $check->execute();
        if ($check->fetch() === false) {
            // Insert the new category
            $insert = $pdo->prepare("
                INSERT INTO categories (slug, name_en, name_ar, sort_order) 
                VALUES ('khinat', 'Khinat', 'Khanaat', 10)
            ");
            $insert->execute();
            echo "Khinat category added successfully!\n";
        } else {
            echo "Khinat category already exists.\n";
        }
    } catch (Throwable $e) {
        echo "Error adding khinat category: " . $e->getMessage() . "\n";
    }
} else {
    echo "Database connection failed.\n";
}
