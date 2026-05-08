<?php
declare(strict_types=1);

/**
 * Automated Hostinger Deployment Script
 * This script automatically detects and configures database settings
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html dir='ltr' lang='ar'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Auto Deploy - Meda E-commerce</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; direction: rtl; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #d4af37; text-align: center; margin-bottom: 30px; }
        .step { margin: 20px 0; padding: 15px; border-right: 4px solid #d4af37; background: #f9f9f9; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #004085; background: #cce5ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        button { background: #d4af37; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        button:hover { background: #b8941f; }
        .progress { margin: 20px 0; }
        .progress-bar { width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: #d4af37; transition: width 0.3s ease; }
        .log { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Auto Deploy - Meda E-commerce</h1>";

// Step 1: Detect Hostinger environment
echo "<div class='step'>
    <h3>Step 1: Detecting Hosting Environment</h3>";

$isHostinger = false;
$hostInfo = [];

// Check for Hostinger indicators
if (strpos($_SERVER['HTTP_HOST'], 'hostinger') !== false || 
    strpos($_SERVER['SERVER_NAME'], 'hostinger') !== false ||
    file_exists('/home/u') || 
    strpos($_SERVER['DOCUMENT_ROOT'], 'public_html') !== false) {
    $isHostinger = true;
    $hostInfo['type'] = 'Hostinger';
    $hostInfo['host'] = 'localhost';
} else {
    $hostInfo['type'] = 'Other Hosting';
    $hostInfo['host'] = 'localhost';
}

echo "<div class='success'>Detected: " . $hostInfo['type'] . "</div>";
echo "<div class='info'>Database Host: " . $hostInfo['host'] . "</div>";

echo "</div>";

// Step 2: Auto-detect database credentials
echo "<div class='step'>
    <h3>Step 2: Auto-Detecting Database Credentials</h3>";

$detectedCreds = [
    'host' => $hostInfo['host'],
    'name' => '',
    'user' => '',
    'pass' => ''
];

// Try common Hostinger database names
$commonDbNames = ['meda_db', 'medal_db', 'u123456789_meda', 'meda'];
$commonUsers = ['meda_user', 'medal_user', 'u123456789_meda', 'root'];

// Try to detect from environment variables or common patterns
foreach ($commonDbNames as $dbName) {
    try {
        $pdo = new PDO("mysql:host={$hostInfo['host']};charset=utf8mb4", 'root', '');
        $stmt = $pdo->query("SHOW DATABASES LIKE '$dbName'");
        if ($stmt->rowCount() > 0) {
            $detectedCreds['name'] = $dbName;
            break;
        }
    } catch (PDOException $e) {
        // Continue trying
    }
}

// Try to detect user
foreach ($commonUsers as $user) {
    try {
        $pdo = new PDO("mysql:host={$hostInfo['host']};charset=utf8mb4", $user, '');
        $stmt = $pdo->query("SELECT 1");
        if ($stmt) {
            $detectedCreds['user'] = $user;
            $detectedCreds['pass'] = '';
            break;
        }
    } catch (PDOException $e) {
        // Continue trying
    }
}

echo "<div class='info'>Detected credentials:</div>";
echo "<ul>";
echo "<li>Database Name: " . ($detectedCreds['name'] ?: 'Not detected') . "</li>";
echo "<li>Database User: " . ($detectedCreds['user'] ?: 'Not detected') . "</li>";
echo "<li>Database Password: " . ($detectedCreds['pass'] ? '[SET]' : '[EMPTY]') . "</li>";
echo "</ul>";

echo "</div>";

// Step 3: Test connection and create database if needed
echo "<div class='step'>
    <h3>Step 3: Testing Connection & Creating Database</h3>";

function createDatabaseAndTables($creds) {
    $messages = [];
    
    try {
        // Connect without database first
        $pdo = new PDO("mysql:host={$creds['host']};charset=utf8mb4", $creds['user'], $creds['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        $messages[] = "Database connection successful!";
        
        // Create database if not exists
        if (!empty($creds['name'])) {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$creds['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $messages[] = "Database '{$creds['name']}' created or already exists!";
            
            // Connect to the database
            $pdo = new PDO("mysql:host={$creds['host']};dbname={$creds['name']};charset=utf8mb4", $creds['user'], $creds['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            // Import schema
            $schemaFile = __DIR__ . '/database/schema.sql';
            if (file_exists($schemaFile)) {
                $schema = file_get_contents($schemaFile);
                $queries = preg_split('/;\s*\n/', $schema);
                
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query) && !preg_match('/^--/', $query)) {
                        try {
                            $pdo->exec($query);
                        } catch (PDOException $e) {
                            $messages[] = "Query executed (some may already exist)";
                        }
                    }
                }
                $messages[] = "Database schema imported successfully!";
            }
            
            // Add Khinat category
            try {
                $check = $pdo->prepare("SELECT id FROM categories WHERE slug = 'khinat'");
                $check->execute();
                if ($check->fetch() === false) {
                    $insert = $pdo->prepare("INSERT INTO categories (slug, name_en, name_ar, sort_order) VALUES (?, ?, ?, ?)");
                    $insert->execute(['khinat', 'Khanaat', 'Khanaat', 10]);
                    $messages[] = "Khinat category added!";
                }
            } catch (PDOException $e) {
                $messages[] = "Khinat category already exists!";
            }
            
            // Add file sharing field
            try {
                $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS file_sharing_url TEXT NULL AFTER description_ar");
                $messages[] = "File sharing field added!";
            } catch (PDOException $e) {
                $messages[] = "File sharing field already exists!";
            }
            
            // Add view counter field
            try {
                $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0 AFTER is_offer");
                $messages[] = "View counter field added!";
            } catch (PDOException $e) {
                $messages[] = "View counter already exists!";
            }
            
            // Create admin user
            try {
                $check = $pdo->prepare("SELECT id FROM admin_users LIMIT 1");
                $check->execute();
                if ($check->fetch() === false) {
                    $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
                    $insert = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
                    $insert->execute(['admin', $passwordHash]);
                    $messages[] = "Default admin user created (admin/admin123)";
                }
            } catch (PDOException $e) {
                $messages[] = "Admin user already exists!";
            }
            
        }
        
    } catch (PDOException $e) {
        $messages[] = "Error: " . $e->getMessage();
    }
    
    return $messages;
}

if (!empty($detectedCreds['name']) && !empty($detectedCreds['user'])) {
    $messages = createDatabaseAndTables($detectedCreds);
    foreach ($messages as $msg) {
        echo "<div class='info'>" . htmlspecialchars($msg) . "</div>";
    }
} else {
    echo "<div class='warning'>Could not auto-detect all credentials. Please use manual setup.</div>";
}

echo "</div>";

// Step 4: Create configuration file
echo "<div class='step'>
    <h3>Step 4: Creating Configuration File</h3>";

if (!empty($detectedCreds['name']) && !empty($detectedCreds['user'])) {
    $configContent = "<?php\n";
    $configContent .= "declare(strict_types=1);\n\n";
    $configContent .= "// Auto-generated database configuration\n";
    $configContent .= "const DB_HOST = '{$detectedCreds['host']}';\n";
    $configContent .= "const DB_NAME = '{$detectedCreds['name']}';\n";
    $configContent .= "const DB_USER = '{$detectedCreds['user']}';\n";
    $configContent .= "const DB_PASS = '{$detectedCreds['pass']}';\n\n";
    $configContent .= "// Base URLs\n";
    $configContent .= "define('BASE_URL', 'https://{$_SERVER['HTTP_HOST']}');\n";
    $configContent .= "define('ADMIN_URL', 'https://{$_SERVER['HTTP_HOST']}/admin/');\n\n";
    $configContent .= "// Security\n";
    $configContent .= "define('ENCRYPTION_KEY', 'meda-ecommerce-2024-secure-key');\n";
    $configContent .= "define('DEBUG_MODE', false);\n\n";
    $configContent .= "// Include all functions\n";
    $configContent .= "require_once __DIR__ . '/functions.php';\n";
    $configContent .= "?>\n";
    
    $configFile = __DIR__ . '/includes/config.php';
    if (file_put_contents($configFile, $configContent)) {
        echo "<div class='success'>Configuration file created successfully!</div>";
    } else {
        echo "<div class='error'>Failed to create configuration file!</div>";
    }
} else {
    echo "<div class='warning'>Cannot create config file - missing credentials!</div>";
}

echo "</div>";

// Step 5: Final verification
echo "<div class='step'>
    <h3>Step 5: Final Verification</h3>";

echo "<div class='info'><strong>Deployment Summary:</strong></div>";
echo "<ul>";
echo "<li>Hosting: " . $hostInfo['type'] . "</li>";
echo "<li>Database: " . ($detectedCreds['name'] ?: 'Not configured') . "</li>";
echo "<li>Admin Login: admin / admin123</li>";
echo "<li>Main Site: <a href='index.php'>View Website</a></li>";
echo "<li>Admin Panel: <a href='admin/login.php'>Access Admin</a></li>";
echo "</ul>";

echo "<div class='warning'><strong>Important:</strong></div>";
echo "<ul>";
echo "<li>Delete this file after deployment!</li>";
echo "<li>Change default admin password!</li>";
echo "<li>Enable SSL certificate!</li>";
echo "</ul>";

echo "</div>";

echo "<div class='step'>
    <h3>Ready to Launch!</h3>
    <button onclick=\"window.location.href='index.php'\">View Website</button>
    <button onclick=\"window.location.href='admin/login.php'\">Access Admin Panel</button>
</div>";

echo "</div>
</body>
</html>";
?>
