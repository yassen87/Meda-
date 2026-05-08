<?php
declare(strict_types=1);

/**
 * Website Debug Script
 * This script helps diagnose and fix website issues
 */

echo "<!DOCTYPE html>
<html dir='rtl' lang='ar'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Website Debug - Meda</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; direction: rtl; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #d4af37; text-align: center; margin-bottom: 30px; }
        .step { margin: 20px 0; padding: 15px; border-right: 4px solid #d4af37; background: #f9f9f9; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #004085; background: #cce5ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        button { background: #d4af37; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #b8941f; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Website Debug - Meda E-commerce</h1>";

// Step 1: Check PHP version and settings
echo "<div class='step'>
    <h3>Step 1: PHP Environment Check</h3>";

echo "<div class='info'>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<strong>Current Directory:</strong> " . __DIR__ . "<br>";
echo "</div>";

// Check required extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (empty($missingExtensions)) {
    echo "<div class='success'>All required PHP extensions are loaded!</div>";
} else {
    echo "<div class='error'>Missing PHP extensions: " . implode(', ', $missingExtensions) . "</div>";
}

echo "</div>";

// Step 2: Check required files
echo "<div class='step'>
    <h3>Step 2: Required Files Check</h3>";

$requiredFiles = [
    'index.php',
    'includes/config.php',
    'includes/db.php',
    'includes/products.php',
    'includes/header.php',
    'includes/footer.php',
    'database/schema.sql'
];

foreach ($requiredFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<div class='success'>File exists: $file</div>";
    } else {
        echo "<div class='error'>Missing file: $file</div>";
    }
}

echo "</div>";

// Step 3: Database connection test
echo "<div class='step'>
    <h3>Step 3: Database Connection Test</h3>";

try {
    // Test without database first
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "<div class='success'>MySQL connection successful!</div>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'medal_db'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>Database 'medal_db' exists!</div>";
        
        // Connect to the database
        $pdo = new PDO('mysql:host=localhost;dbname=medal_db;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // Check tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<div class='info'>Found " . count($tables) . " tables: " . implode(', ', $tables) . "</div>";
        
        // Check essential tables
        $essentialTables = ['products', 'categories', 'orders', 'admin_users'];
        $missingTables = array_diff($essentialTables, $tables);
        
        if (empty($missingTables)) {
            echo "<div class='success'>All essential tables exist!</div>";
        } else {
            echo "<div class='error'>Missing tables: " . implode(', ', $missingTables) . "</div>";
        }
    } else {
        echo "<div class='error'>Database 'medal_db' does not exist!</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>Database connection failed: " . $e->getMessage() . "</div>";
    echo "<div class='warning'>Please make sure MySQL is running and accessible with root user.</div>";
}

echo "</div>";

// Step 4: Test config loading
echo "<div class='step'>
    <h3>Step 4: Configuration Test</h3>";

try {
    require_once __DIR__ . '/includes/config.php';
    echo "<div class='success'>Config file loaded successfully!</div>";
    
    // Test database connection through config
    $pdo = medal_pdo();
    if ($pdo !== null) {
        echo "<div class='success'>Database connection through config works!</div>";
    } else {
        echo "<div class='error'>Database connection through config failed!</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>Config loading failed: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Step 5: Test products loading
echo "<div class='step'>
    <h3>Step 5: Products Loading Test</h3>";

try {
    require_once __DIR__ . '/includes/products.php';
    
    // Test static products
    $products = get_products_static();
    echo "<div class='success'>Static products loaded: " . count($products) . " products</div>";
    
    // Test database products
    $pdo = medal_pdo();
    if ($pdo !== null) {
        $dbProducts = get_products_localized();
        echo "<div class='success'>Database products loaded: " . count($dbProducts) . " products</div>";
    } else {
        echo "<div class='warning'>Database products not available (using static products)</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>Products loading failed: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Step 6: Test homepage loading
echo "<div class='step'>
    <h3>Step 6: Homepage Loading Test</h3>";

try {
    // Simulate homepage loading
    ob_start();
    
    // Test basic includes
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/products.php';
    
    // Test category counts
    $cAll = count_products_in_category('all');
    $cWomen = count_products_in_category('women');
    $cMen = count_products_in_category('men');
    $cUnisex = count_products_in_category('unisex');
    $cKhinat = count_products_in_category('khinat');
    
    echo "<div class='success'>Category counts loaded successfully!</div>";
    echo "<div class='info'>All: $cAll, Women: $cWomen, Men: $cMen, Unisex: $cUnisex, Khinat: $cKhinat</div>";
    
    // Test bestsellers
    $bestsellers = get_bestsellers_localized(4);
    echo "<div class='success'>Bestsellers loaded: " . count($bestsellers) . " products</div>";
    
    ob_end_clean();
    
} catch (Exception $e) {
    echo "<div class='error'>Homepage loading test failed: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Step 7: Provide solutions
echo "<div class='step'>
    <h3>Step 7: Solutions and Next Steps</h3>";

echo "<div class='info'>
        <h4>If you see errors above, here are the solutions:</h4>
        <ul>
            <li><strong>Database connection failed:</strong> Make sure MySQL is running</li>
            <li><strong>Missing database:</strong> Run <a href='setup_database_local.php'>setup_database_local.php</a></li>
            <li><strong>Missing tables:</strong> Run <a href='run_migrate.php'>run_migrate.php</a></li>
            <li><strong>Missing files:</strong> Make sure all files are uploaded correctly</li>
            <li><strong>PHP extensions:</strong> Install required PHP extensions</li>
        </ul>
    </div>";

echo "<div class='success'>
        <h4>Quick Fix Actions:</h4>
        <ol>
            <li><a href='setup_database_local.php'>Setup Database</a> - Creates database and tables</li>
            <li><a href='run_migrate.php'>Run Migration</a> - Adds new features</li>
            <li><a href='index.php'>Test Homepage</a> - Check if website works</li>
            <li><a href='admin/login.php'>Test Admin Panel</a> - Check admin functionality</li>
        </ol>
    </div>";

echo "</div>";

echo "</div>
</body>
</html>";
?>
