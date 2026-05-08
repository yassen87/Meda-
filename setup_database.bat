@echo off
title Database Setup for Meda
color 0B

echo ==========================================
echo     Database Setup for Meda Website
echo ==========================================
echo.

echo Checking database configuration...
echo.

REM Check if database config exists
if not exist "includes\config.php" (
    echo ERROR: Database config file not found!
    echo Please make sure includes\config.php exists
    pause
    exit /b 1
)

REM Test database connection
echo Testing database connection...
php -r "
require_once 'includes/config.php';
try {
    \$pdo = medal_pdo();
    if (\$pdo === null) {
        echo 'Database connection failed!\n';
        exit(1);
    }
    echo 'Database connection successful!\n';
    
    // Check if tables exist
    \$stmt = \$pdo->query('SHOW TABLES');
    \$tables = \$stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty(\$tables)) {
        echo 'No tables found. Running schema setup...\n';
    } else {
        echo 'Tables found: ' . implode(', ', \$tables) . '\n';
    }
} catch (Exception \$e) {
    echo 'Database error: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

if errorlevel 1 (
    echo.
    echo Database connection failed!
    echo Please check your database configuration in includes\config.php
    echo.
    echo Make sure:
    echo 1. MySQL/MariaDB is running
    echo 2. Database 'medal_db' exists
    echo 3. Database credentials are correct
    echo.
    pause
    exit /b 1
)

echo.
echo Step 1: Adding Khinat category...
if exist "database\add_khinat_category.php" (
    php database\add_khinat_category.php
) else (
    echo Khinat category setup file not found, skipping...
)

echo.
echo Step 2: Adding file sharing field...
if exist "database\add_file_sharing_field.php" (
    php database\add_file_sharing_field.php
) else (
    echo File sharing field setup file not found, skipping...
)

echo.
echo Step 3: Running main migration...
if exist "database\migrate.php" (
    php database\migrate.php
) else (
    echo Main migration file not found, skipping...
)

echo.
echo ==========================================
echo Database setup completed!
echo ==========================================
echo.
echo You can now run start_meda.bat to start the website
echo.
pause
