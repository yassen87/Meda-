@echo off
title Deploy Meda to Production (Hostinger)
color 0E

echo ==========================================
echo     Deploy Meda to Production
echo ==========================================
echo.

REM Check if we're in the right directory
if not exist "index.php" (
    echo ERROR: Please run this script from the Meda directory
    echo Current directory: %CD%
    pause
    exit /b 1
)

echo Step 1: Preparing production deployment...
echo.

REM Create production deployment directory
if exist "deploy_production" rmdir /s /q deploy_production
mkdir deploy_production
mkdir deploy_production\assets
mkdir deploy_production\assets\css
mkdir deploy_production\assets\js
mkdir deploy_production\assets\uploads
mkdir deploy_production\admin
mkdir deploy_production\client
mkdir deploy_production\includes
mkdir deploy_production\database
mkdir deploy_production\tmp
mkdir deploy_production\logs

echo Step 2: Copying files...
echo.

REM Copy main files
copy index.php deploy_production\
copy about.php deploy_production\
copy cart.php deploy_production\
copy checkout.php deploy_production\
copy contact.php deploy_production\
copy product.php deploy_production\
copy products.php deploy_production\
copy ajax_apply_promo.php deploy_production\
copy ajax_update_cart.php deploy_production\
copy order_confirm_mail.php deploy_production\
copy .htaccess deploy_production\

REM Copy directories
xcopy assets deploy_production\assets /E /I /Q
xcopy admin deploy_production\admin /E /I /Q
xcopy client deploy_production\client /E /I /Q
xcopy includes deploy_production\includes /E /I /Q
xcopy database deploy_production\database /E /I /Q
xcopy tmp deploy_production\tmp /E /I /Q

echo Step 3: Setting up production database config...
echo.

REM Create production database config with Hostinger credentials
echo ^<?php > deploy_production\includes\db.local.php
echo declare(strict_types=1); >> deploy_production\includes\db.local.php
echo. >> deploy_production\includes\db.local.php
echo // Hostinger Production Database Configuration >> deploy_production\includes\db.local.php
echo define('MEDAL_DB_DSN', 'mysql:host=localhost;dbname=u868008675_zein77_1;charset=utf8mb4'); >> deploy_production\includes\db.local.php
echo define('MEDAL_DB_USER', 'u868008675_zein12'); >> deploy_production\includes\db.local.php
echo define('MEDAL_DB_PASS', ''); >> deploy_production\includes\db.local.php
echo ^?> >> deploy_production\includes\db.local.php

echo Step 4: Creating production .htaccess...
echo.

echo # Production Configuration for Meda E-commerce > deploy_production\.htaccess
echo. >> deploy_production\.htaccess
echo # Enable URL rewriting >> deploy_production\.htaccess
echo RewriteEngine On >> deploy_production\.htaccess
echo. >> deploy_production\.htaccess
echo # Set default character encoding >> deploy_production\.htaccess
echo AddDefaultCharset UTF-8 >> deploy_production\.htaccess
echo. >> deploy_production\.htaccess
echo # PHP settings for production >> deploy_production\.htaccess
echo ^<IfModule mod_php.c^> >> deploy_production\.htaccess
echo     php_flag display_errors Off >> deploy_production\.htaccess
echo     php_flag log_errors On >> deploy_production\.htaccess
echo     php_value error_log logs/php_errors.log >> deploy_production\.htaccess
echo     php_value max_execution_time 300 >> deploy_production\.htaccess
echo     php_value upload_max_filesize 10M >> deploy_production\.htaccess
echo     php_value post_max_size 12M >> deploy_production\.htaccess
echo     php_value memory_limit 256M >> deploy_production\.htaccess
echo ^</IfModule^> >> deploy_production\.htaccess
echo. >> deploy_production\.htaccess
echo # Security headers >> deploy_production\.htaccess
echo ^<IfModule mod_headers.c^> >> deploy_production\.htaccess
echo     Header always set X-Content-Type-Options nosniff >> deploy_production\.htaccess
echo     Header always set X-Frame-Options DENY >> deploy_production\.htaccess
echo     Header always set X-XSS-Protection "1; mode=block" >> deploy_production\.htaccess
echo ^</IfModule^> >> deploy_production\.htaccess
echo. >> deploy_production\.htaccess
echo # Cache static files >> deploy_production\.htaccess
echo ^<IfModule mod_expires.c^> >> deploy_production\.htaccess
echo     ExpiresActive On >> deploy_production\.htaccess
echo     ExpiresByType text/css "access plus 1 month" >> deploy_production\.htaccess
echo     ExpiresByType application/javascript "access plus 1 month" >> deploy_production\.htaccess
echo     ExpiresByType image/png "access plus 1 month" >> deploy_production\.htaccess
echo     ExpiresByType image/jpg "access plus 1 month" >> deploy_production\.htaccess
echo     ExpiresByType image/jpeg "access plus 1 month" >> deploy_production\.htaccess
echo     ExpiresByType image/gif "access plus 1 month" >> deploy_production\.htaccess
echo     ExpiresByType image/svg+xml "access plus 1 month" >> deploy_production\.htaccess
echo ^</IfModule^> >> deploy_production\.htaccess
echo. >> deploy_production\.htaccess
echo # Error pages >> deploy_production\.htaccess
echo ErrorDocument 404 /index.php >> deploy_production\.htaccess
echo ErrorDocument 500 /index.php >> deploy_production\.htaccess
echo. >> deploy_production\.htaccess
echo # Pretty URLs >> deploy_production\.htaccess
echo RewriteRule ^product/([a-zA-Z0-9-]+)$ product.php?slug=$1 [L,QSA] >> deploy_production\.htaccess
echo RewriteRule ^category/([a-zA-Z0-9-]+)$ products.php?cat=$1 [L,QSA] >> deploy_production\.htaccess

echo Step 5: Creating deployment guide...
echo.

echo # Meda Production Deployment Guide > deploy_production\README.md
echo. >> deploy_production\README.md
echo ## Quick Deployment Steps >> deploy_production\README.md
echo. >> deploy_production\README.md
echo 1. Upload all files from this folder to your Hostinger hosting >> deploy_production\README.md
echo 2. Set file permissions (see below) >> deploy_production\README.md
echo 3. Import database schema using phpMyAdmin >> deploy_production\README.md
echo 4. Run admin setup at /admin/setup.php >> deploy_production\README.md
echo 5. Test the website >> deploy_production\README.md
echo. >> deploy_production\README.md
echo ## Database Configuration >> deploy_production\README.md
echo. >> deploy_production\README.md
echo - Database Name: u868008675_zein77_1 >> deploy_production\README.md
echo - Database User: u868008675_zein12 >> deploy_production\README.md
echo - Database Password: [Set your password] >> deploy_production\README.md
echo - Host: localhost >> deploy_production\README.md
echo. >> deploy_production\README.md
echo ## File Permissions >> deploy_production\README.md
echo. >> deploy_production\README.md
echo Set these permissions via FTP or File Manager: >> deploy_production\README.md
echo - `assets/uploads/` - 755 (writable) >> deploy_production\README.md
echo - `tmp/` - 755 (writable) >> deploy_production\README.md
echo - `logs/` - 755 (writable) >> deploy_production\README.md
echo - All PHP files - 644 >> deploy_production\README.md
echo - All folders - 755 >> deploy_production\README.md
echo. >> deploy_production\README.md
echo ## URLs >> deploy_production\README.md
echo. >> deploy_production\README.md
echo - Main Site: https://zeinperfumes.com >> deploy_production\README.md
echo - Admin Panel: https://zeinperfumes.com/admin/ >> deploy_production\README.md
echo - Database Setup: https://zeinperfumes.com/admin/setup.php >> deploy_production\README.md
echo. >> deploy_production\README.md
echo ## Troubleshooting >> deploy_production\README.md
echo. >> deploy_production\README.md
echo ### 500 Error >> deploy_production\README.md
echo 1. Check database credentials in includes/db.local.php >> deploy_production\README.md
echo 2. Ensure database exists and schema is imported >> deploy_production\README.md
echo 3. Check file permissions >> deploy_production\README.md
echo 4. Visit /test_connection.php for diagnostics >> deploy_production\README.md
echo. >> deploy_production\README.md
echo ### Styling Issues >> deploy_production\README.md
echo 1. Ensure .htaccess is uploaded >> deploy_production\README.md
echo 2. Check that assets/css files exist >> deploy_production\README.md
echo 3. Clear browser cache >> deploy_production\README.md

echo Step 6: Creating deployment package...
echo.

REM Create ZIP file
cd deploy_production
powershell -command "Compress-Archive -Path * -DestinationPath ../meda_production.zip -Force"
cd ..

echo.
echo ==========================================
echo Production deployment package created!
echo ==========================================
echo.
echo Files created:
echo - deploy_production/ folder with all website files
echo - meda_production.zip (ready to upload)
echo - deploy_production/README.md (deployment guide)
echo.
echo Database configuration:
echo - Database: u868008675_zein77_1
echo - User: u868008675_zein12
echo - Password: [You need to set this]
echo.
echo Next steps:
echo 1. Upload files from deploy_production/ folder to Hostinger
echo 2. Set database password in Hostinger control panel
echo 3. Update password in includes/db.local.php on server
echo 4. Import database schema via phpMyAdmin
echo 5. Set file permissions
echo 6. Test at https://zeinperfumes.com
echo.
echo Press any key to open the deploy_production folder...
pause >nul

explorer deploy_production

echo.
echo Deployment preparation complete!
pause
