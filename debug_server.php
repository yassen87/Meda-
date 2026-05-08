<?php
// Debug script to identify server issues
header('Content-Type: text/plain; charset=utf-8');

echo "=== Meda Server Debug ===\n\n";

// 1. PHP Version
echo "PHP Version: " . PHP_VERSION . "\n";

// 2. Required extensions
$required_extensions = ['pdo', 'pdo_mysql', 'mysqli', 'json', 'mbstring'];
echo "\nRequired Extensions:\n";
foreach ($required_extensions as $ext) {
    echo "- $ext: " . (extension_loaded($ext) ? "✓" : "✗") . "\n";
}

// 3. File permissions
$writable_dirs = ['assets/uploads', 'tmp', 'logs'];
echo "\nDirectory Permissions:\n";
foreach ($writable_dirs as $dir) {
    if (is_dir($dir)) {
        $writable = is_writable($dir);
        echo "- $dir: " . ($writable ? "✓ Writable" : "✗ Not writable") . "\n";
    } else {
        echo "- $dir: ✗ Directory not found\n";
    }
}

// 4. Database connection test
echo "\nDatabase Connection:\n";
try {
    // Check for local config first
    $config_file = __DIR__ . '/includes/db.local.php';
    if (file_exists($config_file)) {
        echo "- Found local config: db.local.php\n";
        require_once $config_file;
    } else {
        echo "- Using default config\n";
        define('MEDAL_DB_DSN', 'mysql:host=127.0.0.1;dbname=medal_db;charset=utf8mb4');
        define('MEDAL_DB_USER', 'root');
        define('MEDAL_DB_PASS', '');
    }
    
    if (defined('MEDAL_DB_DSN')) {
        $pdo = new PDO(MEDAL_DB_DSN, MEDAL_DB_USER, MEDAL_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        echo "- Connection: ✓ Success\n";
        
        // Check tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "- Tables found: " . implode(', ', $tables) . "\n";
        
        // Check admin_users
        if (in_array('admin_users', $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
            echo "- Admin users: $count\n";
        }
        
        // Check products
        if (in_array('products', $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
            echo "- Products: $count\n";
        }
    }
} catch (Exception $e) {
    echo "- Connection: ✗ Failed - " . $e->getMessage() . "\n";
}

// 5. Session test
echo "\nSession:\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "- Session status: " . session_status() . "\n";
echo "- Session ID: " . session_id() . "\n";

// 6. File structure check
echo "\nRequired Files:\n";
$required_files = [
    'includes/config.php',
    'includes/db.php', 
    'admin/_init.php',
    'admin/products.php',
    'admin/login.php',
    'admin/setup.php'
];

foreach ($required_files as $file) {
    echo "- $file: " . (file_exists($file) ? "✓" : "✗") . "\n";
}

echo "\n=== End Debug ===\n";
?>
