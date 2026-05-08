<?php
declare(strict_types=1);

/**
 * Local Database Setup Script
 * This script creates the database and tables for local development
 */

echo "<!DOCTYPE html>
<html dir='rtl' lang='ar'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Setup - Meda</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; direction: rtl; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #d4af37; text-align: center; margin-bottom: 30px; }
        .step { margin: 20px 0; padding: 15px; border-right: 4px solid #d4af37; background: #f9f9f9; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #004085; background: #cce5ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        button { background: #d4af37; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #b8941f; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Database Setup - Meda E-commerce</h1>";

// Step 1: Test database connection
echo "<div class='step'>
    <h3>Step 1: Testing Database Connection</h3>";

try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "<div class='success'>Database connection successful!</div>";
} catch (PDOException $e) {
    echo "<div class='error'>Database connection failed: " . $e->getMessage() . "</div>";
    echo "<p>Please make sure MySQL is running and accessible with root user.</p>";
    echo "</div></body></html>";
    exit;
}

echo "</div>";

// Step 2: Create database
echo "<div class='step'>
    <h3>Step 2: Creating Database</h3>";

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS medal_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='success'>Database 'medal_db' created or already exists!</div>";
    
    // Connect to the database
    $pdo = new PDO('mysql:host=localhost;dbname=medal_db;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "<div class='error'>Failed to create database: " . $e->getMessage() . "</div>";
    echo "</div></body></html>";
    exit;
}

echo "</div>";

// Step 3: Import schema
echo "<div class='step'>
    <h3>Step 3: Creating Tables</h3>";

$schemaFile = __DIR__ . '/database/schema.sql';
if (file_exists($schemaFile)) {
    $schema = file_get_contents($schemaFile);
    $queries = preg_split('/;\s*\n/', $schema);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query) && !preg_match('/^--/', $query)) {
            try {
                $pdo->exec($query);
                echo "<div class='success'>Table created successfully!</div>";
            } catch (PDOException $e) {
                echo "<div class='info'>Query executed (table may already exist): " . $e->getMessage() . "</div>";
            }
        }
    }
    echo "<div class='success'>Database schema imported!</div>";
} else {
    echo "<div class='error'>Schema file not found: database/schema.sql</div>";
}

echo "</div>";

// Step 4: Add Khinat category
echo "<div class='step'>
    <h3>Step 4: Adding Khinat Category</h3>";

try {
    $check = $pdo->prepare("SELECT id FROM categories WHERE slug = 'khinat'");
    $check->execute();
    if ($check->fetch() === false) {
        $insert = $pdo->prepare("INSERT INTO categories (slug, name_en, name_ar, sort_order) VALUES (?, ?, ?, ?)");
        $insert->execute(['khinat', 'Khanaat', 'Khanaat', 10]);
        echo "<div class='success'>Khinat category added!</div>";
    } else {
        echo "<div class='info'>Khinat category already exists!</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>Error adding Khinat category: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Step 5: Add admin user
echo "<div class='step'>
    <h3>Step 5: Creating Admin User</h3>";

try {
    $check = $pdo->prepare("SELECT id FROM admin_users LIMIT 1");
    $check->execute();
    if ($check->fetch() === false) {
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
        $insert->execute(['admin', $passwordHash]);
        echo "<div class='success'>Default admin user created!</div>";
        echo "<p>Username: admin</p>";
        echo "<p>Password: admin123</p>";
        echo "<p><strong>Please change this password after login!</strong></p>";
    } else {
        echo "<div class='info'>Admin user already exists!</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>Error creating admin user: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Step 6: Add additional fields
echo "<div class='step'>
    <h3>Step 6: Adding Additional Fields</h3>";

// Add view_count to products
try {
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0 AFTER is_offer");
    echo "<div class='success'>View count field added to products!</div>";
} catch (PDOException $e) {
    echo "<div class='info'>View count field already exists or error: " . $e->getMessage() . "</div>";
}

// Add file_sharing_url to products
try {
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS file_sharing_url TEXT NULL AFTER description_ar");
    echo "<div class='success'>File sharing URL field added to products!</div>";
} catch (PDOException $e) {
    echo "<div class='info'>File sharing URL field already exists or error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Step 7: Final verification
echo "<div class='step'>
    <h3>Step 7: Final Verification</h3>";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<div class='success'>Found " . count($tables) . " tables: " . implode(', ', $tables) . "</div>";
    
    // Check if essential tables exist
    $essentialTables = ['products', 'categories', 'orders', 'admin_users'];
    $missingTables = array_diff($essentialTables, $tables);
    
    if (empty($missingTables)) {
        echo "<div class='success'>All essential tables are present!</div>";
    } else {
        echo "<div class='error'>Missing tables: " . implode(', ', $missingTables) . "</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>Error checking tables: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='step'>
    <h3>Setup Complete!</h3>";

echo "<div class='success'>
        <h4>Database setup completed successfully!</h4>
        <p>You can now access:</p>
        <ul>
            <li><a href='index.php'>Main Website</a></li>
            <li><a href='admin/login.php'>Admin Panel</a></li>
        </ul>
        <p><strong>Admin Login:</strong> admin / admin123</p>
        <p><strong>Important:</strong> Change the default password after first login!</p>
    </div>";

echo "</div>";

echo "</div>
</body>
</html>";
?>
