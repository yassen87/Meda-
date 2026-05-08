@echo off
title Setup Local Meda Database
color 0E

echo ==========================================
echo     Setup Local Meda Database
echo ==========================================
echo.

REM Check if PHP is available
set PHP_CMD=php
php -v >nul 2>&1
if errorlevel 1 (
    if exist "C:\xampp\php\php.exe" (
        set PHP_CMD=C:\xampp\php\php.exe
    ) else (
        echo ERROR: PHP not found!
        echo Please install PHP or use XAMPP
        pause
        exit /b 1
    )
)

echo Step 1: Creating database...
%PHP_CMD% -r "
try {
    \$pdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
    \$pdo->exec('CREATE DATABASE IF NOT EXISTS medal_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo 'Database medal_db created successfully.\n';
} catch (Exception \$e) {
    echo 'Database creation failed: ' . \$e->getMessage() . '\n';
}
"

echo.
echo Step 2: Importing schema...
if exist "database\schema.sql" (
    echo Importing schema.sql...
    %PHP_CMD% -r "
    try {
        \$pdo = new PDO('mysql:host=127.0.0.1;dbname=medal_db;charset=utf8mb4', 'root', '');
        \$sql = file_get_contents('database/schema.sql');
        \$pdo->exec(\$sql);
        echo 'Schema imported successfully.\n';
    } catch (Exception \$e) {
        echo 'Schema import failed: ' . \$e->getMessage() . '\n';
    }
    "
) else (
    echo Schema file not found!
)

echo.
echo Step 3: Running migrations...
if exist "database\migrate.php" (
    %PHP_CMD% database\migrate.php
    echo Migration completed!
) else (
    echo Migration file not found, skipping...
)

echo.
echo Step 4: Creating admin user...
%PHP_CMD% -r "
require_once 'includes/config.php';
require_once 'includes/db.php';

\$pdo = medal_pdo();
if (\$pdo) {
    try {
        \$st = \$pdo->prepare('INSERT INTO admin_users (username, password_hash, role, permissions) VALUES (?, ?, ?, ?)');
        \$password = password_hash('admin123', PASSWORD_DEFAULT);
        \$st->execute(['admin', \$password, 'superadmin', '']);
        echo 'Admin user created: username=admin, password=admin123\n';
    } catch (Exception \$e) {
        echo 'Admin user creation failed: ' . \$e->getMessage() . '\n';
    }
} else {
    echo 'Database connection failed.\n';
}
"

echo.
echo ==========================================
echo Local database setup completed!
echo ==========================================
echo.
echo Admin login:
echo Username: admin
echo Password: admin123
echo.
echo Access admin panel at: http://localhost:8000/admin/
echo.
pause
