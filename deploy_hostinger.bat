@echo off
title Deploy Meda to Hostinger
color 0E

echo ==========================================
echo     Deploy Meda to Hostinger
echo ==========================================
echo.

REM Check if we're in the right directory
if not exist "index.php" (
    echo ERROR: Please run this script from the Meda directory
    echo Current directory: %CD%
    pause
    exit /b 1
)

REM Create deployment directory
if exist "deploy" rmdir /s /q deploy
mkdir deploy
mkdir deploy\assets
mkdir deploy\assets\css
mkdir deploy\assets\js
mkdir deploy\assets\uploads
mkdir deploy\admin
mkdir deploy\client
mkdir deploy\includes
mkdir deploy\database
mkdir deploy\tmp

echo Step 1: Preparing files for deployment...
echo.

REM Copy main files
copy index.php deploy\
copy about.php deploy\
copy cart.php deploy\
copy checkout.php deploy\
copy contact.php deploy\
copy product.php deploy\
copy products.php deploy\
copy ajax_apply_promo.php deploy\
copy ajax_update_cart.php deploy\
copy order_confirm_mail.php deploy\

REM Copy directories
xcopy assets deploy\assets /E /I /Q
xcopy admin deploy\admin /E /I /Q
xcopy client deploy\client /E /I /Q
xcopy includes deploy\includes /E /I /Q
xcopy database deploy\database /E /I /Q
xcopy tmp deploy\tmp /E /I /Q

REM Copy .htaccess
if exist ".htaccess" copy .htaccess deploy\

echo Step 2: Creating Hostinger-specific configuration...
echo.

REM Create Hostinger database config
echo ^<?php > deploy\includes\config_hostinger.php
echo. >> deploy\includes\config_hostinger.php
echo // Hostinger Database Configuration >> deploy\includes\config_hostinger.php
echo const DB_HOST = 'localhost'; // Usually localhost on Hostinger >> deploy\includes\config_hostinger.php
echo const DB_NAME = 'your_database_name'; // Replace with your Hostinger database name >> deploy\includes\config_hostinger.php
echo const DB_USER = 'your_database_user'; // Replace with your Hostinger database user >> deploy\includes\config_hostinger.php
echo const DB_PASS = 'your_database_password'; // Replace with your Hostinger database password >> deploy\includes\config_hostinger.php
echo. >> deploy\includes\config_hostinger.php
echo // Base URLs >> deploy\includes\config_hostinger.php
echo define('BASE_URL', 'https://yourdomain.com'); // Replace with your domain >> deploy\includes\config_hostinger.php
echo define('ADMIN_URL', 'https://yourdomain.com/admin/'); // Replace with your domain >> deploy\includes\config_hostinger.php
echo. >> deploy\includes\config_hostinger.php
echo // Security >> deploy\includes\config_hostinger.php
echo define('ENCRYPTION_KEY', 'your-32-character-encryption-key-here'); >> deploy\includes\config_hostinger.php
echo ^?> >> deploy\includes\config_hostinger.php

echo Step 3: Creating Hostinger .htaccess...
echo.

REM Create Hostinger-optimized .htaccess
echo # Hostinger Configuration for Meda E-commerce > deploy\.htaccess
echo. >> deploy\.htaccess
echo # Enable URL rewriting >> deploy\.htaccess
echo RewriteEngine On >> deploy\.htaccess
echo. >> deploy\.htaccess
echo # Set default character encoding >> deploy\.htaccess
echo AddDefaultCharset UTF-8 >> deploy\.htaccess
echo. >> deploy\.htaccess
echo # PHP settings for Hostinger >> deploy\.htaccess
echo ^<IfModule mod_php.c^> >> deploy\.htaccess
echo     php_flag display_errors Off >> deploy\.htaccess
echo     php_flag log_errors On >> deploy\.htaccess
echo     php_value error_log logs/php_errors.log >> deploy\.htaccess
echo     php_value max_execution_time 300 >> deploy\.htaccess
echo     php_value upload_max_filesize 10M >> deploy\.htaccess
echo     php_value post_max_size 12M >> deploy\.htaccess
echo     php_value memory_limit 256M >> deploy\.htaccess
echo ^</IfModule^> >> deploy\.htaccess
echo. >> deploy\.htaccess
echo # Security headers >> deploy\.htaccess
echo ^<IfModule mod_headers.c^> >> deploy\.htaccess
echo     Header always set X-Content-Type-Options nosniff >> deploy\.htaccess
echo     Header always set X-Frame-Options DENY >> deploy\.htaccess
echo     Header always set X-XSS-Protection "1; mode=block" >> deploy\.htaccess
echo ^</IfModule^> >> deploy\.htaccess
echo. >> deploy\.htaccess
echo # Cache static files >> deploy\.htaccess
echo ^<IfModule mod_expires.c^> >> deploy\.htaccess
echo     ExpiresActive On >> deploy\.htaccess
echo     ExpiresByType text/css "access plus 1 month" >> deploy\.htaccess
echo     ExpiresByType application/javascript "access plus 1 month" >> deploy\.htaccess
echo     ExpiresByType image/png "access plus 1 month" >> deploy\.htaccess
echo     ExpiresByType image/jpg "access plus 1 month" >> deploy\.htaccess
echo     ExpiresByType image/jpeg "access plus 1 month" >> deploy\.htaccess
echo     ExpiresByType image/gif "access plus 1 month" >> deploy\.htaccess
echo     ExpiresByType image/svg+xml "access plus 1 month" >> deploy\.htaccess
echo ^</IfModule^> >> deploy\.htaccess
echo. >> deploy\.htaccess
echo # Error pages >> deploy\.htaccess
echo ErrorDocument 404 /index.php >> deploy\.htaccess
echo ErrorDocument 500 /index.php >> deploy\.htaccess
echo. >> deploy\.htaccess
echo # Pretty URLs >> deploy\.htaccess
echo RewriteRule ^product/([a-zA-Z0-9-]+)$ product.php?slug=$1 [L,QSA] >> deploy\.htaccess
echo RewriteRule ^category/([a-zA-Z0-9-]+)$ products.php?cat=$1 [L,QSA] >> deploy\.htaccess

echo Step 4: Creating deployment package...
echo.

REM Create ZIP file
cd deploy
powershell -command "Compress-Archive -Path * -DestinationPath ../meda_hostinger_deploy.zip -Force"
cd ..

echo Step 5: Creating deployment instructions...
echo.

echo # Hostinger Deployment Instructions > deploy_instructions.md
echo. >> deploy_instructions.md
echo ## Quick Start >> deploy_instructions.md
echo. >> deploy_instructions.md
echo 1. Upload all files from the `deploy` folder to your Hostinger hosting >> deploy_instructions.md
echo 2. Update database settings in `includes/config_hostinger.php` >> deploy_instructions.md
echo 3. Import the database schema using Hostinger's phpMyAdmin >> deploy_instructions.md
echo 4. Set proper permissions for `assets/uploads/` folder (755) >> deploy_instructions.md
echo. >> deploy_instructions.md
echo ## Database Setup >> deploy_instructions.md
echo. >> deploy_instructions.md
echo 1. Log in to Hostinger Control Panel >> deploy_instructions.md
echo 2. Go to phpMyAdmin >> deploy_instructions.md
echo 3. Create a new database >> deploy_instructions.md
echo 4. Import the `database/schema.sql` file >> deploy_instructions.md
echo 5. Run the migration scripts from `database/` folder >> deploy_instructions.md
echo. >> deploy_instructions.md
echo ## File Permissions >> deploy_instructions.md
echo. >> deploy_instructions.md
echo - Set `assets/uploads/` folder to 755 permissions >> deploy_instructions.md
echo - Set `tmp/` folder to 755 permissions >> deploy_instructions.md
echo - Ensure all PHP files have 644 permissions >> deploy_instructions.md
echo. >> deploy_instructions.md
echo ## URL Configuration >> deploy_instructions.md
echo. >> deploy_instructions.md
echo Update these lines in `includes/config_hostinger.php`: >> deploy_instructions.md
echo ```php >> deploy_instructions.md
echo define('BASE_URL', 'https://yourdomain.com'); >> deploy_instructions.md
echo define('ADMIN_URL', 'https://yourdomain.com/admin/'); >> deploy_instructions.md
echo ``` >> deploy_instructions.md
echo. >> deploy_instructions.md
echo ## Testing >> deploy_instructions.md
echo. >> deploy_instructions.md
echo After deployment, test: >> deploy_instructions.md
echo - Main website: https://yourdomain.com >> deploy_instructions.md
echo - Admin panel: https://yourdomain.com/admin/ >> deploy_instructions.md
echo - Khinat products: https://yourdomain.com/products.php?cat=khinat >> deploy_instructions.md

echo.
echo ==========================================
echo Deployment package created successfully!
echo ==========================================
echo.
echo Files created:
echo - deploy/ folder with all website files
echo - meda_hostinger_deploy.zip (ready to upload)
echo - deploy_instructions.md (detailed guide)
echo.
echo Next steps:
echo 1. Upload files from deploy/ folder to Hostinger
echo 2. Update database settings in config_hostinger.php
echo 3. Import database schema
echo 4. Test the website
echo.
echo Press any key to open the deploy folder...
pause >nul

explorer deploy

echo.
echo Deployment preparation complete!
pause
