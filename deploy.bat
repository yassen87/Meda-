@echo off
title Meda E-commerce Deployment
color 0B

echo ==========================================
echo     Meda E-commerce Deployment
echo ==========================================
echo.

REM Check if we're in the right directory
if not exist "index.php" (
    echo ERROR: Please run this script from the Meda directory
    echo Current directory: %CD%
    pause
    exit /b 1
)

:menu
echo Choose deployment target:
echo.
echo 1. Hostinger Deployment
echo 2. Local Server Setup
echo 3. Generic Deployment Package
echo 4. Exit
echo.
set /p choice="Enter your choice (1-4): "

if "%choice%"=="1" goto hostinger
if "%choice%"=="2" goto local
if "%choice%"=="3" goto generic
if "%choice%"=="4" goto exit
echo Invalid choice! Please try again.
echo.
goto menu

:hostinger
echo.
echo Starting Hostinger deployment...
call deploy_hostinger.bat
goto menu

:local
echo.
echo Starting local server setup...
call start_meda.bat
goto menu

:generic
echo.
echo Creating generic deployment package...
goto create_generic

:create_generic
REM Create deployment directory
if exist "deploy_generic" rmdir /s /q deploy_generic
mkdir deploy_generic
mkdir deploy_generic\assets
mkdir deploy_generic\assets\css
mkdir deploy_generic\assets\js
mkdir deploy_generic\assets\uploads
mkdir deploy_generic\admin
mkdir deploy_generic\client
mkdir deploy_generic\includes
mkdir deploy_generic\database
mkdir deploy_generic\tmp
mkdir deploy_generic\logs

echo Step 1: Preparing files for deployment...
echo.

REM Copy main files
copy index.php deploy_generic\
copy about.php deploy_generic\
copy cart.php deploy_generic\
copy checkout.php deploy_generic\
copy contact.php deploy_generic\
copy product.php deploy_generic\
copy products.php deploy_generic\
copy ajax_apply_promo.php deploy_generic\
copy ajax_update_cart.php deploy_generic\
copy order_confirm_mail.php deploy_generic\
copy .htaccess deploy_generic\

REM Copy directories
xcopy assets deploy_generic\assets /E /I /Q
xcopy admin deploy_generic\admin /E /I /Q
xcopy client deploy_generic\client /E /I /Q
xcopy includes deploy_generic\includes /E /I /Q
xcopy database deploy_generic\database /E /I /Q
xcopy tmp deploy_generic\tmp /E /I /Q

echo Step 2: Creating configuration template...
echo.

REM Create config template
echo ^<?php > deploy_generic\includes\config_template.php
echo. >> deploy_generic\includes\config_template.php
echo // Database Configuration >> deploy_generic\includes\config_template.php
echo const DB_HOST = 'localhost'; // Database host >> deploy_generic\includes\config_template.php
echo const DB_NAME = 'your_database_name'; // Database name >> deploy_generic\includes\config_template.php
echo const DB_USER = 'your_database_user'; // Database user >> deploy_generic\includes\config_template.php
echo const DB_PASS = 'your_database_password'; // Database password >> deploy_generic\includes\config_template.php
echo. >> deploy_generic\includes\config_template.php
echo // Base URLs >> deploy_generic\includes\config_template.php
echo define('BASE_URL', 'https://yourdomain.com'); // Your domain >> deploy_generic\includes\config_template.php
echo define('ADMIN_URL', 'https://yourdomain.com/admin/'); // Admin URL >> deploy_generic\includes\config_template.php
echo. >> deploy_generic\includes\config_template.php
echo // Security >> deploy_generic\includes\config_template.php
echo define('ENCRYPTION_KEY', 'your-32-character-encryption-key-here'); >> deploy_generic\includes\config_template.php
echo ^?> >> deploy_generic\includes\config_template.php

echo Step 3: Creating deployment guide...
echo.

echo # Meda E-commerce Deployment Guide > deploy_generic\README.md
echo. >> deploy_generic\README.md
echo ## Quick Deployment Steps >> deploy_generic\README.md
echo. >> deploy_generic\README.md
echo 1. Upload all files to your hosting server >> deploy_generic\README.md
echo 2. Rename `config_template.php` to `config.php` >> deploy_generic\README.md
echo 3. Update database settings in `config.php` >> deploy_generic\README.md
echo 4. Import database schema from `database/schema.sql` >> deploy_generic\README.md
echo 5. Set file permissions >> deploy_generic\README.md
echo. >> deploy_generic\README.md
echo ## Database Setup >> deploy_generic\README.md
echo. >> deploy_generic\README.md
echo 1. Create a MySQL database >> deploy_generic\README.md
echo 2. Import the SQL file from `database/schema.sql` >> deploy_generic\README.md
echo 3. Update database credentials in `config.php` >> deploy_generic\README.md
echo. >> deploy_generic\README.md
echo ## File Permissions >> deploy_generic\README.md
echo. >> deploy_generic\README.md
echo - `assets/uploads/` - 755 (writable) >> deploy_generic\README.md
echo - `tmp/` - 755 (writable) >> deploy_generic\README.md
echo - `logs/` - 755 (writable) >> deploy_generic\README.md
echo - All PHP files - 644 >> deploy_generic\README.md
echo. >> deploy_generic\README.md
echo ## URL Configuration >> deploy_generic\README.md
echo. >> deploy_generic\README.md
echo Update these lines in `config.php`: >> deploy_generic\README.md
echo ```php >> deploy_generic\README.md
echo define('BASE_URL', 'https://yourdomain.com'); >> deploy_generic\README.md
echo define('ADMIN_URL', 'https://yourdomain.com/admin/'); >> deploy_generic\README.md
echo ``` >> deploy_generic\README.md
echo. >> deploy_generic\README.md
echo ## Features >> deploy_generic\README.md
echo. >> deploy_generic\README.md
echo - E-commerce platform with product management >> deploy_generic\README.md
echo - Shopping cart and checkout system >> deploy_generic\README.md
echo - Admin panel for content management >> deploy_generic\README.md
echo - Khinat products category >> deploy_generic\README.md
echo - Contact form and order management >> deploy_generic\README.md

echo Step 4: Creating deployment package...
echo.

REM Create ZIP file
cd deploy_generic
powershell -command "Compress-Archive -Path * -DestinationPath ../meda_deploy.zip -Force"
cd ..

echo.
echo ==========================================
echo Generic deployment package created!
echo ==========================================
echo.
echo Files created:
echo - deploy_generic/ folder with all website files
echo - meda_deploy.zip (ready to upload)
echo - deploy_generic/README.md (deployment guide)
echo.
echo Next steps:
echo 1. Upload files from deploy_generic/ folder to your hosting
echo 2. Follow the instructions in README.md
echo 3. Test the website
echo.
echo Press any key to open the deploy_generic folder...
pause >nul

explorer deploy_generic
goto menu

:exit
echo.
echo Deployment process completed.
pause
exit /b 0
