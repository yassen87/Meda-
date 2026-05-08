@echo off
title Meda Local Development Server
color 0A

echo ==========================================
echo     Meda Local Development Server
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
        echo.
        echo If you have XAMPP installed, use start_xampp.bat instead
        pause
        exit /b 1
    )
)

REM Check if we're in the right directory
if not exist "index.php" (
    echo ERROR: Please run this script from the Meda directory
    echo Current directory: %CD%
    pause
    exit /b 1
)

echo Step 1: Setting up local database configuration...
echo.

REM Backup current config and use local config
if exist "includes\db.local.php" (
    copy "includes\db.local.php" "includes\db.local.backup.php"
    copy "includes\db.local.dev.php" "includes\db.local.php"
    echo Local database configuration activated.
) else (
    copy "includes\db.local.dev.php" "includes\db.local.php"
    echo Local database configuration created.
)

echo Step 2: Starting PHP development server...
echo Server will run on http://localhost:8000
echo.

REM Create logs directory if it doesn't exist
if not exist "logs" mkdir logs

REM Start PHP server in background
start /B %PHP_CMD% -S localhost:8000 > logs\server.log 2>&1

REM Wait for server to start
timeout /t 3 /nobreak >nul

echo Step 3: Creating local database...
if exist "database\schema.sql" (
    echo Creating database schema...
    %PHP_CMD% -r "
    try {
        \$pdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        \$pdo->exec('CREATE DATABASE IF NOT EXISTS medal_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        echo 'Database created successfully.\n';
    } catch (Exception \$e) {
        echo 'Database creation failed: ' . \$e->getMessage() . '\n';
    }
    "
    
    echo Importing schema...
    %PHP_CMD% database\migrate.php
    echo Database setup completed!
) else (
    echo Schema file not found, skipping database creation...
)

echo.
echo Step 4: Opening website in browser...
echo Website URL: http://localhost:8000
echo Admin Panel: http://localhost:8000/admin/
echo.

REM Open browser
start http://localhost:8000

echo.
echo ==========================================
echo Local development server is running!
echo Press Ctrl+C to stop the server
echo ==========================================
echo.

REM Keep the window open
echo Press any key to stop the server...
pause >nul

REM Stop the server (kill PHP processes)
taskkill /F /IM php.exe >nul 2>&1

echo.
echo Step 5: Restoring original configuration...
if exist "includes\db.local.backup.php" (
    copy "includes\db.local.backup.php" "includes\db.local.php"
    del "includes\db.local.backup.php"
    echo Original configuration restored.
)

echo Server stopped.
pause
