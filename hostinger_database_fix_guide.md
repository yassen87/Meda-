# Hostinger Database Fix Guide

## Problem: Database Connection Error in Admin Panel

You're getting a "database not configured" error when trying to access the admin panel on Hostinger. This is a common issue that can be fixed with these steps.

## Quick Fix Solution

### Step 1: Run Database Setup Script
1. Upload `fix_hostinger_database.php` to your Hostinger hosting
2. Access it via browser: `https://yourdomain.com/fix_hostinger_database.php`
3. Follow the on-screen instructions

### Step 2: Manual Database Setup (if automatic fails)

#### 2.1 Create Database in Hostinger
1. Log in to Hostinger Control Panel
2. Go to **MySQL Databases**
3. Click **Create Database**
4. Enter:
   - Database name: `meda_db`
   - Database user: `meda_user`
   - Password: [create strong password]
5. Click **Create**

#### 2.2 Import Database Schema
1. In Hostinger Control Panel, go to **phpMyAdmin**
2. Select your new database (`meda_db`)
3. Click **Import**
4. Upload `database/schema.sql`
5. Click **Go**

#### 2.3 Update Configuration
Edit `includes/config_hostinger.php`:

```php
<?php
// Update these with your actual database details
const DB_HOST = 'localhost';
const DB_NAME = 'meda_db';        // Your database name
const DB_USER = 'meda_user';      // Your database user
const DB_PASS = 'your_password';  // Your database password

// Update URLs
define('BASE_URL', 'https://yourdomain.com');
define('ADMIN_URL', 'https://yourdomain.com/admin/');
?>
```

#### 2.4 Run Additional Setup
Access these URLs to complete setup:
- `https://yourdomain.com/database/add_khinat_category.php`
- `https://yourdomain.com/database/add_file_sharing_field.php`
- `https://yourdomain.com/database/migrate.php`

## Common Issues and Solutions

### Issue 1: "Access denied for user"
**Solution**: Check database username and password in Hostinger Control Panel

### Issue 2: "Database doesn't exist"
**Solution**: Create the database first in Hostinger MySQL Databases section

### Issue 3: "Connection refused"
**Solution**: Use 'localhost' as DB_HOST (don't use IP address)

### Issue 4: "Table doesn't exist"
**Solution**: Import the schema.sql file in phpMyAdmin

## Verification Steps

After setup, verify everything works:

1. **Database Connection**: Access `fix_hostinger_database.php` - should show "Setup Complete!"
2. **Admin Panel**: Go to `https://yourdomain.com/admin/login.php`
3. **Default Login**: 
   - Username: `admin`
   - Password: `admin123`
4. **Change Password**: Immediately change the default password

## Hostinger Specific Settings

### PHP Configuration
Make sure these PHP settings are enabled in Hostinger:
- `mysqli` extension
- `pdo_mysql` extension
- `mbstring` extension
- `file_uploads` enabled

### File Permissions
Set these permissions via Hostinger File Manager:
- `includes/config_hostinger.php` - 644
- `database/` folder - 755
- All PHP files - 644

### Security
1. Delete `fix_hostinger_database.php` after use
2. Change default admin password
3. Enable SSL certificate
4. Set up regular backups

## Alternative: Using Hostinger's Database Wizard

If the manual method doesn't work:

1. In Hostinger Control Panel, look for **MySQL Database Wizard**
2. Follow the wizard to create database and user
3. Grant all permissions
4. Import schema.sql via phpMyAdmin
5. Update configuration file

## Troubleshooting Checklist

- [ ] Database created in Hostinger
- [ ] User has correct permissions
- [ ] Schema imported successfully
- [ ] Configuration file updated
- [ ] File permissions correct
- [ ] PHP extensions enabled
- [ ] SSL certificate active
- [ ] Admin login working

## Support Resources

If you're still having issues:

1. **Hostinger Knowledge Base**: https://support.hostinger.com/
2. **phpMyAdmin Documentation**: https://www.phpmyadmin.net/docs/
3. **Contact Hostinger Support**: Use live chat in your control panel

## Quick Command Summary

```bash
# Upload files to Hostinger
# Run setup script
https://yourdomain.com/fix_hostinger_database.php

# Or manual setup:
# 1. Create database in Hostinger Control Panel
# 2. Import schema.sql via phpMyAdmin
# 3. Update config_hostinger.php
# 4. Run migration scripts
# 5. Test admin login
```

---

**After following these steps, your database should be properly configured and the admin panel should work!**
