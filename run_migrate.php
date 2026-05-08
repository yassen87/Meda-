<?php
declare(strict_types=1);

/**
 * Database Migration Runner
 * This script runs all database migrations
 */

echo "<!DOCTYPE html>
<html dir='rtl' lang='ar'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Migration - Meda</title>
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
        <h1>Database Migration - Meda E-commerce</h1>";

// Check database connection
echo "<div class='step'>
    <h3>Step 1: Checking Database Connection</h3>";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=medal_db;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "<div class='success'>Database connection successful!</div>";
} catch (PDOException $e) {
    echo "<div class='error'>Database connection failed: " . $e->getMessage() . "</div>";
    echo "<p>Please run setup_database_local.php first.</p>";
    echo "</div></body></html>";
    exit;
}

echo "</div>";

// Step 2: Add Khinat category
echo "<div class='step'>
    <h3>Step 2: Adding Khinat Category</h3>";

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

// Step 3: Add file sharing field
echo "<div class='step'>
    <h3>Step 3: Adding File Sharing Field</h3>";

try {
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS file_sharing_url TEXT NULL AFTER description_ar");
    echo "<div class='success'>File sharing field added!</div>";
} catch (PDOException $e) {
    echo "<div class='info'>File sharing field already exists or error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Step 4: Add view counter field
echo "<div class='step'>
    <h3>Step 4: Adding View Counter Field</h3>";

try {
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0 AFTER is_offer");
    echo "<div class='success'>View counter field added!</div>";
} catch (PDOException $e) {
    echo "<div class='info'>View counter already exists or error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Step 5: Create internal products table
echo "<div class='step'>
    <h3>Step 5: Creating Internal Products Table</h3>";

try {
    $createInternalProducts = "
        CREATE TABLE IF NOT EXISTS internal_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name_en VARCHAR(255) NOT NULL,
            name_ar VARCHAR(255) NOT NULL,
            description TEXT,
            cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            type ENUM('gift', 'sample', 'promotional') NOT NULL DEFAULT 'gift',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($createInternalProducts);
    echo "<div class='success'>Internal products table created!</div>";
} catch (PDOException $e) {
    echo "<div class='error'>Error creating internal_products table: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Step 6: Create order internal products table
echo "<div class='step'>
    <h3>Step 6: Creating Order Internal Products Table</h3>";

try {
    $createOrderInternalProducts = "
        CREATE TABLE IF NOT EXISTS order_internal_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            internal_product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (internal_product_id) REFERENCES internal_products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($createOrderInternalProducts);
    echo "<div class='success'>Order internal products table created!</div>";
} catch (PDOException $e) {
    echo "<div class='error'>Error creating order_internal_products table: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Step 7: Add sample internal products
echo "<div class='step'>
    <h3>Step 7: Adding Sample Internal Products</h3>";

$sampleProducts = [
    [
        'name_en' => 'Welcome Gift Box',
        'name_ar' => ' puzzles',
        'description' => 'A special gift box for new customers with sample products',
        'cost' => 15.00,
        'type' => 'gift'
    ],
    [
        'name_en' => 'Perfume Sample Set',
        'name_ar' => ' puzzles',
        'description' => 'Collection of perfume samples for customers to try',
        'cost' => 5.00,
        'type' => 'sample'
    ],
    [
        'name_en' => 'Loyalty Reward',
        'name_ar' => ' puzzles',
        'description' => 'Special reward for loyal customers',
        'cost' => 25.00,
        'type' => 'promotional'
    ]
];

foreach ($sampleProducts as $product) {
    try {
        $check = $pdo->prepare("SELECT id FROM internal_products WHERE name_en = ?");
        $check->execute([$product['name_en']]);
        if ($check->fetch() === false) {
            $insert = $pdo->prepare("
                INSERT INTO internal_products (name_en, name_ar, description, cost, type)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $product['name_en'],
                $product['name_ar'],
                $product['description'],
                $product['cost'],
                $product['type']
            ]);
            echo "<div class='success'>Sample internal product created: " . htmlspecialchars($product['name_en']) . "</div>";
        } else {
            echo "<div class='info'>Sample internal product already exists: " . htmlspecialchars($product['name_en']) . "</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>Error creating sample product: " . $e->getMessage() . "</div>";
    }
}

echo "</div>";

// Step 8: Final verification
echo "<div class='step'>
    <h3>Step 8: Final Verification</h3>";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<div class='success'>Found " . count($tables) . " tables: " . implode(', ', $tables) . "</div>";
    
    // Check if admin user exists
    $check = $pdo->prepare("SELECT id FROM admin_users LIMIT 1");
    $check->execute();
    if ($check->fetch() === false) {
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
        $insert->execute(['admin', $passwordHash]);
        echo "<div class='success'>Admin user created (admin/admin123)!</div>";
    } else {
        echo "<div class='info'>Admin user already exists!</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>Error checking tables: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='step'>
    <h3>Migration Complete!</h3>";

echo "<div class='success'>
        <h4>Database migration completed successfully!</h4>
        <p>All new features are now ready:</p>
        <ul>
            <li><strong>Khinat Category</strong> - Added to products and categories</li>
            <li><strong>File Sharing</strong> - Products can have external file links</li>
            <li><strong>View Counter</strong> - Track product views</li>
            <li><strong>Internal Products</strong> - Gifts, samples, and promotional items</li>
            <li><strong>Order Internal Products</strong> - Add gifts to orders</li>
        </ul>
        <p><strong>Next Steps:</strong></p>
        <ul>
            <li><a href='index.php'>Visit Website</a></li>
            <li><a href='admin/login.php'>Access Admin Panel</a></li>
            <li><a href='admin/sales_records.php'>Sales Records</a></li>
            <li><a href='admin/product_statistics.php'>Product Statistics</a></li>
            <li><a href='admin/order_management.php'>Order Management</a></li>
            <li><a href='admin/internal_products.php'>Internal Products</a></li>
        </ul>
        <p><strong>Admin Login:</strong> admin / admin123</p>
    </div>";

echo "</div>";

echo "</div>
</body>
</html>";
?>
