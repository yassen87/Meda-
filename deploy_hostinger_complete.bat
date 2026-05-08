@echo off
title Deploy Meda Complete to Hostinger
color 0E

echo ==========================================
echo     Deploy Meda Complete to Hostinger
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
if exist "deploy_complete" rmdir /s /q deploy_complete
mkdir deploy_complete
mkdir deploy_complete\assets
mkdir deploy_complete\assets\css
mkdir deploy_complete\assets\js
mkdir deploy_complete\assets\uploads
mkdir deploy_complete\admin
mkdir deploy_complete\includes
mkdir deploy_complete\database
mkdir deploy_complete\tmp

echo Step 1: Preparing files for deployment...
echo.

REM Copy main files
copy index.php deploy_complete\
copy about.php deploy_complete\
copy cart.php deploy_complete\
copy checkout.php deploy_complete\
copy contact.php deploy_complete\
copy product.php deploy_complete\
copy products.php deploy_complete\
copy ajax_apply_promo.php deploy_complete\
copy ajax_update_cart.php deploy_complete\
copy auto_deploy_hostinger.php deploy_complete\

REM Copy new admin files
copy admin\sales_records.php deploy_complete\admin\
copy admin\product_statistics.php deploy_complete\admin\
copy admin\order_management.php deploy_complete\admin\
copy admin\internal_products.php deploy_complete\admin\

REM Copy directories
xcopy assets deploy_complete\assets /E /I /Q
xcopy admin deploy_complete\admin /E /I /Q
xcopy includes deploy_complete\includes /E /I /Q
xcopy database deploy_complete\database /E /I /Q
xcopy tmp deploy_complete\tmp /E /I /Q

REM Copy .htaccess
if exist ".htaccess" copy .htaccess deploy_complete\

echo Step 2: Creating Hostinger-specific configuration...
echo.

REM Create Hostinger database config
echo ^<?php > deploy_complete\includes\config_hostinger.php
echo. >> deploy_complete\includes\config_hostinger.php
echo // Hostinger Database Configuration >> deploy_complete\includes\config_hostinger.php
echo const DB_HOST = 'localhost'; // Usually localhost on Hostinger >> deploy_complete\includes\config_hostinger.php
echo const DB_NAME = 'your_database_name'; // Replace with your Hostinger database name >> deploy_complete\includes\config_hostinger.php
echo const DB_USER = 'your_database_user'; // Replace with your Hostinger database user >> deploy_complete\includes\config_hostinger.php
echo const DB_PASS = 'your_database_password'; // Replace with your Hostinger database password >> deploy_complete\includes\config_hostinger.php
echo. >> deploy_complete\includes\config_hostinger.php
echo // Base URLs >> deploy_complete\includes\config_hostinger.php
echo define('BASE_URL', 'https://yourdomain.com'); // Replace with your domain >> deploy_complete\includes\config_hostinger.php
echo define('ADMIN_URL', 'https://yourdomain.com/admin/'); // Replace with your domain >> deploy_complete\includes\config_hostinger.php
echo. >> deploy_complete\includes\config_hostinger.php
echo // Security >> deploy_complete\includes\config_hostinger.php
echo define('ENCRYPTION_KEY', 'your-32-character-encryption-key-here'); >> deploy_complete\includes\config_hostinger.php
echo ^?> >> deploy_complete\includes\config_hostinger.php

echo Step 3: Creating enhanced .htaccess...
echo.

REM Create enhanced .htaccess
echo # Hostinger Configuration for Meda E-commerce with Admin Features > deploy_complete\.htaccess
echo. >> deploy_complete\.htaccess
echo # Enable URL rewriting >> deploy_complete\.htaccess
echo RewriteEngine On >> deploy_complete\.htaccess
echo. >> deploy_complete\.htaccess
echo # Set default character encoding >> deploy_complete\.htaccess
echo AddDefaultCharset UTF-8 >> deploy_complete\.htaccess
echo. >> deploy_complete\.htaccess
echo # PHP settings for Hostinger >> deploy_complete\.htaccess
echo ^<IfModule mod_php.c^> >> deploy_complete\.htaccess
echo     php_flag display_errors Off >> deploy_complete\.htaccess
echo     php_flag log_errors On >> deploy_complete\.htaccess
echo     php_value error_log logs/php_errors.log >> deploy_complete\.htaccess
echo     php_value max_execution_time 300 >> deploy_complete\.htaccess
echo     php_value upload_max_filesize 10M >> deploy_complete\.htaccess
echo     php_value post_max_size 12M >> deploy_complete\.htaccess
echo     php_value memory_limit 256M >> deploy_complete\.htaccess
echo ^</IfModule^> >> deploy_complete\.htaccess
echo. >> deploy_complete\.htaccess
echo # Security headers >> deploy_complete\.htaccess
echo ^<IfModule mod_headers.c^> >> deploy_complete\.htaccess
echo     Header always set X-Content-Type-Options nosniff >> deploy_complete\.htaccess
echo     Header always set X-Frame-Options DENY >> deploy_complete\.htaccess
echo     Header always set X-XSS-Protection "1; mode=block" >> deploy_complete\.htaccess
echo ^</IfModule^> >> deploy_complete\.htaccess
echo. >> deploy_complete\.htaccess
echo # Cache static files >> deploy_complete\.htaccess
echo ^<IfModule mod_expires.c^> >> deploy_complete\.htaccess
echo     ExpiresActive On >> deploy_complete\.htaccess
echo     ExpiresByType text/css "access plus 1 month" >> deploy_complete\.htaccess
echo     ExpiresByType application/javascript "access plus 1 month" >> deploy_complete\.htaccess
echo     ExpiresByType image/png "access plus 1 month" >> deploy_complete\.htaccess
echo     ExpiresByType image/jpg "access plus 1 month" >> deploy_complete\.htaccess
echo     ExpiresByType image/jpeg "access plus 1 month" >> deploy_complete\.htaccess
echo     ExpiresByType image/gif "access plus 1 month" >> deploy_complete\.htaccess
echo     ExpiresByType image/svg+xml "access plus 1 month" >> deploy_complete\.htaccess
echo ^</IfModule^> >> deploy_complete\.htaccess
echo. >> deploy_complete\.htaccess
echo # Error pages >> deploy_complete\.htaccess
echo ErrorDocument 404 /index.php >> deploy_complete\.htaccess
echo ErrorDocument 500 /index.php >> deploy_complete\.htaccess
echo. >> deploy_complete\.htaccess
echo # Pretty URLs >> deploy_complete\.htaccess
echo RewriteRule ^product/([a-zA-Z0-9-]+)$ product.php?slug=$1 [L,QSA] >> deploy_complete\.htaccess
echo RewriteRule ^category/([a-zA-Z0-9-]+)$ products.php?cat=$1 [L,QSA] >> deploy_complete\.htaccess

echo Step 4: Creating deployment package...
echo.

REM Create ZIP file
cd deploy_complete
powershell -command "Compress-Archive -Path * -DestinationPath ../meda_complete_hostinger_deploy.zip -Force"
cd ..

echo Step 5: Creating deployment instructions...
echo.

echo # Meda Complete Hostinger Deployment > deploy_complete_instructions.md
echo. >> deploy_complete_instructions.md
echo ## New Features Included >> deploy_complete_instructions.md
echo. >> deploy_complete_instructions.md
echo ### Admin Panel Enhancements: >> deploy_complete_instructions.md
echo - **Sales Records**: View all customer orders with details and export to Excel >> deploy_complete_instructions.md
echo - **Product Statistics**: Track product views and performance analytics >> deploy_complete_instructions.md
echo - **Order Management**: Edit orders, customer details, and add/remove items >> deploy_complete_instructions.md
echo - **Internal Products**: Manage gifts, samples, and promotional items >> deploy_complete_instructions.md
echo. >> deploy_complete_instructions.md
echo ### Features: >> deploy_complete_instructions.md
echo - Date filtering for orders and sales >> deploy_complete_instructions.md
echo - Excel export for customer data >> deploy_complete_instructions.md
echo - Product view counters and statistics >> deploy_complete_instructions.md
echo - Order editing capabilities >> deploy_complete_instructions.md
echo - Internal products for gifts and promotions >> deploy_complete_instructions.md
echo - Enhanced customer data management >> deploy_complete_instructions.md
echo. >> deploy_complete_instructions.md
echo ## Quick Deployment Steps >> deploy_complete_instructions.md
echo. >> deploy_complete_instructions.md
echo 1. Upload all files from the `deploy_complete` folder to your Hostinger hosting >> deploy_complete_instructions.md
echo 2. Run `auto_deploy_hostinger.php` to auto-configure database >> deploy_complete_instructions.md
echo 3. Run `database/update_schema_for_admin_features.php` to add new tables >> deploy_complete_instructions.md
echo 4. Access admin panel and test new features >> deploy_complete_instructions.md
echo. >> deploy_complete_instructions.md
echo ## New Admin Pages >> deploy_complete_instructions.md
echo. >> deploy_complete_instructions.md
echo - `/admin/sales_records.php` - Sales records with Excel export >> deploy_complete_instructions.md
echo - `/admin/product_statistics.php` - Product view statistics >> deploy_complete_instructions.md
echo - `/admin/order_management.php` - Order management and editing >> deploy_complete_instructions.md
echo - `/admin/internal_products.php` - Internal products management >> deploy_complete_instructions.md
echo. >> deploy_complete_instructions.md
echo ## Database Updates >> deploy_complete_instructions.md
echo. >> deploy_complete_instructions.md
echo The deployment includes: >> deploy_complete_instructions.md
echo - `view_count` column for products >> deploy_complete_instructions.md
echo - `internal_products` table for gifts and samples >> deploy_complete_instructions.md
echo - `order_internal_products` table for order gifts >> deploy_complete_instructions.md
echo - `file_sharing_url` column for products >> deploy_complete_instructions.md
echo. >> deploy_complete_instructions.md
echo ## Default Login >> deploy_complete_instructions.md
echo. >> deploy_complete_instructions.md
echo - Username: admin >> deploy_complete_instructions.md
echo - Password: admin123 >> deploy_complete_instructions.md
echo. >> deploy_complete_instructions.md
echo **Important: Change the default password after first login!** >> deploy_complete_instructions.md

echo.
echo ==========================================
echo Complete deployment package created successfully!
echo ==========================================
echo.
echo Files created:
echo - deploy_complete/ folder with all website files
echo - meda_complete_hostinger_deploy.zip (ready to upload)
echo - deploy_complete_instructions.md (detailed guide)
echo.
echo New features included:
echo - Sales Records with Excel export
echo - Product Statistics and Analytics
echo - Order Management with editing
echo - Internal Products for gifts
echo - Enhanced customer data management
echo.
echo Deployment steps:
echo 1. Upload files from deploy_complete/ folder to Hostinger
echo 2. Run auto_deploy_hostinger.php
echo 3. Run database/update_schema_for_admin_features.php
echo 4. Test all new admin features
echo.
echo Press any key to open the deploy folder...
pause >nul

explorer deploy_complete

echo.
echo Complete deployment preparation finished!
echo Your enhanced Meda e-commerce website is ready for Hostinger!
pause
