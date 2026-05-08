<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$pdo = medal_pdo();
if (!$pdo) {
    die("Database connection failed.");
}

try {
    // 1. Add permissions and role columns to admin_users if they don't exist
    $pdo->exec("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'admin' AFTER password_hash");
    $pdo->exec("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS permissions TEXT NULL AFTER role");

    // 2. Set the existing user as 'superadmin' so they don't lose access
    $pdo->exec("UPDATE admin_users SET role = 'superadmin' WHERE id = 1");

    echo "✅ Database updated successfully. 'role' and 'permissions' columns added.";
} catch (Throwable $e) {
    die("❌ Error updating database: " . $e->getMessage());
}
