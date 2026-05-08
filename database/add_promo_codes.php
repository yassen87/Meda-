<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$pdo = medal_pdo();
if ($pdo === null) {
    fwrite(STDERR, "Cannot connect to MySQL.\n");
    exit(1);
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS promo_codes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(64) NOT NULL UNIQUE,
            discount_percentage INT NOT NULL DEFAULT 0,
            usage_limit INT NOT NULL DEFAULT 0,
            used_count INT NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ");
    echo "Created promo_codes table.\n";

    // Alter orders table
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN promo_code VARCHAR(64) NULL AFTER admin_notes");
        echo "Added promo_code to orders.\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column name')) {
            echo "promo_code column already exists.\n";
        } else {
            throw $e;
        }
    }

    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) NULL AFTER subtotal");
        echo "Added discount_amount to orders.\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column name')) {
            echo "discount_amount column already exists.\n";
        } else {
            throw $e;
        }
    }

    echo "Migration completed successfully.\n";

} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
