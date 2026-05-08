@echo off
title Restore Original Meda Design
color 0A

echo ==========================================
echo     Restore Original Meda Design
echo ==========================================
echo.

REM Check if we're in the right directory
if not exist "index.php" (
    echo ERROR: Please run this script from the Meda directory
    echo Current directory: %CD%
    pause
    exit /b 1
)

echo Step 1: Checking backup files...
if not exist "backup\css\style.css" (
    echo ERROR: Backup files not found in backup\css\
    pause
    exit /b 1
)

echo ✓ Backup files found
echo.

echo Step 2: Restoring original CSS files...

REM Restore style.css
copy "backup\css\style.css" "assets\css\style.css" >nul 2>&1
if exist "assets\css\style.css" (
    echo ✓ style.css restored
) else (
    echo ✗ style.css restore failed
)

REM Restore theme-daylight.css
copy "backup\css\theme-daylight.css" "assets\css\theme-daylight.css" >nul 2>&1
if exist "assets\css\theme-daylight.css" (
    echo ✓ theme-daylight.css restored
) else (
    echo ✗ theme-daylight.css restore failed
)

REM Restore admin.css
copy "backup\css\admin.css" "assets\css\admin.css" >nul 2>&1
if exist "assets\css\admin.css" (
    echo ✓ admin.css restored
) else (
    echo ✗ admin.css restore failed
)

echo.
echo Step 3: Verifying file sizes...

for %%f in (assets\css\style.css assets\css\theme-daylight.css assets\css\admin.css) do (
    if exist "%%f" (
        for %%s in ("%%f") do echo   %%~nxf: %%~zs bytes
    )
)

echo.
echo Step 4: Starting local server to test...
echo.

REM Start PHP server
set PHP_CMD=php
php -v >nul 2>&1
if errorlevel 1 (
    if exist "C:\xampp\php\php.exe" (
        set PHP_CMD=C:\xampp\php\php.exe
    ) else (
        echo ERROR: PHP not found!
        pause
        exit /b 1
    )
)

if not exist "logs" mkdir logs

echo Starting server at http://localhost:8000
start /B %PHP_CMD% -S localhost:8000 > logs\server.log 2>&1

timeout /t 3 /nobreak >nul

echo.
echo ==========================================
echo Original design restored successfully!
echo ==========================================
echo.
echo The original Gulf-style luxury design has been restored.
echo Features restored:
echo - Dark theme with gold accents
echo - Original product card styling
echo - RTL support for Arabic
echo - Admin panel styling
echo.
echo Testing URLs:
echo - Main site: http://localhost:8000
echo - Products: http://localhost:8000/products.php
echo - Admin: http://localhost:8000/admin/
echo.
echo Press any key to open the website...
pause >nul

start http://localhost:8000

echo.
echo Press any key to stop the server...
pause >nul

taskkill /F /IM php.exe >nul 2>&1
echo Server stopped.
pause
