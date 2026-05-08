<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';

$pdo = medal_pdo();
if (!$pdo) die("Database connection failed.");

try {
    // 1. Ensure columns exist (Repair mode)
    $pdo->exec("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'admin'");
    $pdo->exec("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS permissions TEXT NULL");
    
    $username = 'admin';
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // 2. Check if admin user already exists
    $st = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
    $st->execute([$username]);
    $exists = $st->fetch();

    if ($exists) {
        // Update existing admin to be superadmin with new password
        $st = $pdo->prepare("UPDATE admin_users SET password_hash = ?, role = 'superadmin' WHERE username = ?");
        $st->execute([$hash, $username]);
        echo "✅ Database Repaired & Password Reset!<br>User: <b>$username</b><br>Pass: <b>$password</b>";
    } else {
        // Create new superadmin
        $st = $pdo->prepare("INSERT INTO admin_users (username, password_hash, role) VALUES (?, ?, 'superadmin')");
        $st->execute([$username, $hash]);
        echo "✅ Database Repaired & New Superadmin created!<br>User: <b>$username</b><br>Pass: <b>$password</b>";
    }
} catch (Throwable $e) {
    echo "❌ Error during repair: " . $e->getMessage();
}
