@echo off
title Meda E-commerce Website Starter
color 0A

echo ==========================================
echo     Meda E-commerce Website Starter
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

echo Step 1: Starting PHP development server...
echo Server will run on http://localhost:8000
echo.

REM Create logs directory if it doesn't exist
if not exist "logs" mkdir logs

REM Start PHP server in background
start /B %PHP_CMD% -S localhost:8000 > logs\server.log 2>&1

REM Wait for server to start
timeout /t 3 /nobreak >nul

echo Step 2: Running database migration...
if exist "database\migrate.php" (
    echo Running migrate.php...
    %PHP_CMD% database\migrate.php
    echo Database migration completed!
) else (
    echo Migration file not found, skipping...
)

echo.
echo Step 3: Opening website in browser...
echo Website URL: http://localhost:8000
echo Admin Panel: http://localhost:8000/admin/
echo.

REM Open browser
start http://localhost:8000

echo.
echo ==========================================
echo Website is now running!
echo Press Ctrl+C to stop the server
echo ==========================================
echo.

REM Keep the window open
echo Press any key to stop the server...
pause >nul

REM Stop the server (kill PHP processes)
taskkill /F /IM php.exe >nul 2>&1

echo Server stopped.
pause
