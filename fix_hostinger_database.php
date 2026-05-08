<?php
declare(strict_types=1);

/**
 * Database Setup and Diagnostic Script for Hostinger
 * Run this script to fix database connection issues
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html dir='ltr' lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Setup - Meda</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #d4af37; text-align: center; margin-bottom: 30px; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #d4af37; background: #f9f9f9; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #004085; background: #cce5ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        code { background: #f8f9fa; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
        .config-form { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type='text'], input[type='password'] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { background: #d4af37; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #b8941f; }
        .progress { margin: 20px 0; }
        .progress-bar { width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: #d4af37; transition: width 0.3s ease; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Database Setup for Meda E-commerce</h1>";

// Step 1: Check if config file exists
echo "<div class='step'>
    <h3>Step 1: Checking Configuration File</h3>";

$configFile = __DIR__ . '/includes/config_hostinger.php';
$localDbFile = __DIR__ . '/includes/db.local.php';

if (!file_exists($configFile) && !file_exists($localDbFile)) {
    echo "<div class='error'>Configuration file not found (checked includes/config_hostinger.php and includes/db.local.php)</div>";
    echo "<p>Please use the form at the bottom of this page to create your configuration.</p>";
} else {
    if (file_exists($configFile)) {
        require_once $configFile;
        echo "<div class='success'>Configuration file found: includes/config_hostinger.php</div>";
    } elseif (file_exists($localDbFile)) {
        require_once $localDbFile;
        echo "<div class='success'>Configuration file found: includes/db.local.php</div>";
    }
    
    echo "<div class='info'>Current configuration detected.</div>";
}

echo "</div>";

// Step 2: Test database connection
echo "<div class='step'>
    <h3>Step 2: Testing Database Connection</h3>";

function testDatabaseConnection() {
    if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS')) {
        // Try fallback to MEDAL_DB constants if db.local.php was loaded
        if (defined('MEDAL_DB_DSN')) {
            try {
                $pdo = new PDO(MEDAL_DB_DSN, MEDAL_DB_USER, MEDAL_DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                echo "<div class='success'>Database connection successful (using MEDAL_DB settings)!</div>";
                return $pdo;
            } catch (PDOException $e) {
                echo "<div class='error'>Connection failed with MEDAL_DB settings: " . $e->getMessage() . "</div>";
            }
        }
        echo "<div class='warning'>Database credentials are not defined yet. Please use the form below to configure them.</div>";
        return null;
    }
    
    try {
        $dsn = sprintf('mysql:host=%s;charset=utf8mb4', DB_HOST);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        echo "<div class='success'>Database connection successful!</div>";
        return $pdo;
    } catch (PDOException $e) {
        echo "<div class='error'>Database connection failed: " . $e->getMessage() . "</div>";
        return null;
    }
}

$pdo = testDatabaseConnection();

if ($pdo) {
    // Step 3: Check if database exists
    echo "<div class='step'>
        <h3>Step 3: Checking Database</h3>";
    
    try {
        $stmt = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
        $dbExists = $stmt->rowCount() > 0;
        
        if ($dbExists) {
            echo "<div class='success'>Database '" . DB_NAME . "' exists!</div>";
            
            // Connect to the specific database
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            // Check if tables exist
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tables)) {
                echo "<div class='warning'>Database exists but no tables found. Need to create tables.</div>";
                $needTables = true;
            } else {
                echo "<div class='success'>Found " . count($tables) . " tables: " . implode(', ', $tables) . "</div>";
                $needTables = false;
            }
        } else {
            echo "<div class='error'>Database '" . DB_NAME . "' does not exist!</div>";
            echo "<p>You need to create the database first in Hostinger Control Panel.</p>";
            $needDb = true;
        }
    } catch (PDOException $e) {
        echo "<div class='error'>Error checking database: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
    
    // Step 4: Create database and tables if needed
    if (isset($needDb) && $needDb) {
        echo "<div class='step'>
            <h3>Step 4: Creating Database</h3>";
        
        try {
            @$pdo->exec("CREATE DATABASE `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "<div class='success'>Database created successfully!</div>";
            
            // Connect to the new database
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            $needTables = true;
        } catch (PDOException $e) {
            echo "<div class='error'>Note: System cannot create the database automatically (Access Denied).</div>";
            echo "<p>On Hostinger, you <strong>MUST</strong> create the database manually through the Control Panel (hPanel):</p>
            <ol>
                <li>Go to <strong>MySQL Databases</strong> in Hostinger hPanel.</li>
                <li>Create a database name (it will look like <code>u868008675_name</code>).</li>
                <li>Create a database user and password.</li>
                <li>Copy those values to <code>includes/config_hostinger.php</code>.</li>
            </ol>";
        }
        
        echo "</div>";
    }
    
    if (isset($needTables) && $needTables) {
        echo "<div class='step'>
            <h3>Step 5: Creating Tables</h3>";
        
        // Read and execute schema
        $schemaFile = __DIR__ . '/database/schema.sql';
        if (file_exists($schemaFile)) {
            $schema = file_get_contents($schemaFile);
            
            // Split into individual queries
            $queries = preg_split('/;\s*\n/', $schema);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query) && !preg_match('/^--/', $query)) {
                    try {
                        $pdo->exec($query);
                        echo "<div class='success'>Table created successfully!</div>";
                    } catch (PDOException $e) {
                        echo "<div class='error'>Error creating table: " . $e->getMessage() . "</div>";
                    }
                }
            }
            
            echo "<div class='success'>Database schema imported!</div>";
        } else {
            echo "<div class='error'>Schema file not found: database/schema.sql</div>";
        }
        
        echo "</div>";
    }
    
    // Step 6: Run additional setup scripts
    echo "<div class='step'>
        <h3>Step 6: Running Additional Setup</h3>";
    
    // Add Khinat category
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
    
    // Add file sharing field
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS file_sharing_url TEXT NULL AFTER description_ar");
        echo "<div class='success'>File sharing field added!</div>";
    } catch (PDOException $e) {
        echo "<div class='info'>File sharing field already exists or error: " . $e->getMessage() . "</div>";
    }
    
    // Add email_conf_sent field for order tracking
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS email_conf_sent TINYINT(1) DEFAULT 0");
        echo "<div class='success'>Order email tracking field added!</div>";
    } catch (PDOException $e) {
        echo "<div class='info'>Order email tracking field already exists or error: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
    
    // Step 7: Create Internal Products Tables
    echo "<div class='step'>
        <h3>Step 7: Creating Additional Tables (Clients, Students, Gifts)</h3>";
    try {
        // Internal Products
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS internal_products (
                id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name_en VARCHAR(255) NOT NULL,
                name_ar VARCHAR(255) NOT NULL,
                description TEXT,
                cost DECIMAL(10, 2) DEFAULT 0.00,
                type ENUM('gift', 'sample', 'promotional') DEFAULT 'gift',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;
        ");
        echo "<div class='success'>Internal products table verified!</div>";
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS order_internal_products (
                id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id INT(10) UNSIGNED NOT NULL,
                internal_product_id INT(10) UNSIGNED NOT NULL,
                quantity INT DEFAULT 1,
                added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_oip_order_host FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
        echo "<div class='success'>Order internal products table verified!</div>";

        // Clients Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS clients (
                id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                phone VARCHAR(64) DEFAULT NULL,
                name VARCHAR(255) DEFAULT NULL,
                password_hash VARCHAR(255) DEFAULT NULL,
                otp_code VARCHAR(10) DEFAULT NULL,
                otp_expires_at DATETIME DEFAULT NULL,
                is_verified TINYINT(1) DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;
        ");
        echo "<div class='success'>Clients table verified!</div>";

        // Students Table (if needed by your system)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS students (
                id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                client_id INT(10) UNSIGNED NOT NULL,
                name VARCHAR(255) NOT NULL,
                level VARCHAR(100) DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'active',
                notes TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_students_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
        echo "<div class='success'>Students table verified!</div>";

    } catch (PDOException $e) {
        echo "<div class='error'>Error creating tables: " . $e->getMessage() . "</div>";
    }
    echo "</div>";
    
    // Step 8: Test admin user
    echo "<div class='step'>
        <h3>Step 8: Checking Admin User</h3>";
    
    try {
        $stmt = $pdo->query("SELECT * FROM admin_users LIMIT 1");
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "<div class='success'>Admin user found!</div>";
            echo "<p>Username: " . htmlspecialchars($admin['username']) . "</p>";
        } else {
            echo "<div class='warning'>No admin user found!</div>";
            echo "<p>You need to create an admin user.</p>";
            
            // Create default admin user
            $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
            $insert = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
            $insert->execute(['admin', $passwordHash]);
            echo "<div class='success'>Default admin user created!</div>";
            echo "<p>Username: admin</p>";
            echo "<p>Password: admin123</p>";
            echo "<p><strong>Please change this password after login!</strong></p>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>Error checking admin user: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
    
    // Final success message
    echo "<div class='step'>
        <h3>Setup Complete!</h3>";
    
    echo "<div class='success'>
        <h4>Database setup completed successfully!</h4>
        <p>You can now access:</p>
        <ul>
            <li><a href='index.php'>Main Website</a></li>
            <li><a href='admin/login.php'>Admin Panel</a></li>
        </ul>
    </div>";
    
    echo "</div>";
}

// Configuration form for manual setup
echo "<div class='config-form'>
    <h3>Manual Database Configuration</h3>
    <p>If the automatic setup failed, you can manually configure your database:</p>
    
    <form method='post'>
        <div class='form-group'>
            <label for='db_host'>Database Host:</label>
            <input type='text' id='db_host' name='db_host' value='localhost' required>
        </div>
        
        <div class='form-group'>
            <label for='db_name'>Database Name:</label>
            <input type='text' id='db_name' name='db_name' placeholder='your_database_name' required>
        </div>
        
        <div class='form-group'>
            <label for='db_user'>Database User:</label>
            <input type='text' id='db_user' name='db_user' placeholder='your_database_user' required>
        </div>
        
        <div class='form-group'>
            <label for='db_pass'>Database Password:</label>
            <input type='password' id='db_pass' name='db_pass' placeholder='your_database_password' required>
        </div>
        
        <button type='submit'>Test Connection & Setup</button>
    </form>
</div>";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_host'])) {
    $dbHost = $_POST['db_host'];
    $dbName = $_POST['db_name'];
    $dbUser = $_POST['db_user'];
    $dbPass = $_POST['db_pass'];
    
    // Update configuration file
    $configContent = "<?php\n";
    $configContent .= "declare(strict_types=1);\n\n";
    $configContent .= "// Hostinger Database Configuration\n";
    $configContent .= "const DB_HOST = '{$dbHost}';\n";
    $configContent .= "const DB_NAME = '{$dbName}';\n";
    $configContent .= "const DB_USER = '{$dbUser}';\n";
    $configContent .= "const DB_PASS = '{$dbPass}';\n\n";
    $configContent .= "// Base URLs\n";
    $configContent .= "define('BASE_URL', 'https://yourdomain.com');\n";
    $configContent .= "define('ADMIN_URL', 'https://yourdomain.com/admin/');\n\n";
    $configContent .= "// Security\n";
    $configContent .= "define('ENCRYPTION_KEY', 'your-32-character-encryption-key-here');\n";
    $configContent .= "?>\n";
    
    if (file_put_contents($configFile, $configContent)) {
        echo "<div class='success'>Configuration updated! Please refresh the page.</div>";
        echo "<script>setTimeout(() => window.location.reload(), 2000);</script>";
    } else {
        echo "<div class='error'>Failed to update configuration file!</div>";
    }
}

echo "</div>
</body>
</html>";
?>
