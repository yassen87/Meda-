# Hostinger Deployment Guide for Meda E-commerce Website

## Overview
This guide will help you deploy your Meda e-commerce website to Hostinger hosting with all the new features including Khinat products, image upload, and file sharing functionality.

## Prerequisites
- Hostinger hosting account (Shared, Premium, or Business plan)
- Domain name pointing to Hostinger
- Access to Hostinger Control Panel
- FTP client (FileZilla, WinSCP, or Hostinger's File Manager)

## Step 1: Prepare Deployment Package

Run the deployment script to create a ready-to-upload package:
```bash
deploy_hostinger.bat
```

This will create:
- `deploy/` folder with all website files
- `meda_hostinger_deploy.zip` (compressed package)
- `deploy_instructions.md` (detailed instructions)

## Step 2: Upload Files to Hostinger

### Option A: Using Hostinger File Manager
1. Log in to Hostinger Control Panel
2. Go to File Manager
3. Navigate to `public_html/`
4. Upload all files from the `deploy/` folder
5. Extract the ZIP file if you uploaded the ZIP

### Option B: Using FTP
1. Get your FTP credentials from Hostinger Control Panel
2. Connect using FileZilla or similar
3. Navigate to `public_html/`
4. Upload all files from the `deploy/` folder

## Step 3: Configure Database

### 3.1 Create Database
1. In Hostinger Control Panel, go to **MySQL Databases**
2. Create a new database:
   - Database name: `meda_db`
   - Database user: `meda_user`
   - Password: generate a strong password
3. Grant all privileges to the user

### 3.2 Import Database Schema
1. Go to **phpMyAdmin** in Hostinger Control Panel
2. Select your new database
3. Click **Import**
4. Upload the `database/schema.sql` file
5. Click **Go** to import

### 3.3 Run Migration Scripts
1. After importing the schema, run these migration scripts:
   - `database/add_khinat_category.php`
   - `database/add_file_sharing_field.php`
   - `database/migrate.php`

## Step 4: Update Configuration

Edit `includes/config_hostinger.php`:

```php
// Update these values
const DB_HOST = 'localhost';
const DB_NAME = 'meda_db'; // Your database name
const DB_USER = 'meda_user'; // Your database user
const DB_PASS = 'your_password'; // Your database password

// Update URLs
define('BASE_URL', 'https://yourdomain.com'); // Your domain
define('ADMIN_URL', 'https://yourdomain.com/admin/');

// Generate a secure encryption key
define('ENCRYPTION_KEY', 'your-32-character-key-here');
```

## Step 5: Set File Permissions

Using Hostinger File Manager or FTP, set these permissions:

- `assets/uploads/` - 755 (read/write/execute)
- `tmp/` - 755 (read/write/execute)
- All PHP files - 644 (read/write)
- All CSS/JS files - 644 (read/write)
- `.htaccess` - 644 (read/write)

## Step 6: Test the Website

### Basic Testing
1. **Main Website**: `https://yourdomain.com`
2. **Admin Panel**: `https://yourdomain.com/admin/`
3. **Khinat Products**: `https://yourdomain.com/products.php?cat=khinat`

### Feature Testing
1. **Product Categories**: Check if Khinat category appears
2. **Image Upload**: Test image upload in admin panel
3. **File Sharing**: Add a Mega.nz link to a product
4. **Multi-language**: Test English/Arabic switch
5. **Responsive Design**: Test on mobile devices

## Step 7: Final Configuration

### SSL Certificate
1. In Hostinger Control Panel, go to **SSL**
2. Install free Let's Encrypt SSL certificate
3. Force HTTPS redirect

### Step 8: Configure Cron Job (Automated Confirmation)
1. In Hostinger Control Panel, go to **Advanced** > **Cron Jobs**
2. Add a new Cron Job:
   - **Common settings**: Once per hour (or every 6 hours)
   - **Command**: `curl -s "https://yourdomain.com/order_confirm_mail.php?run=cron"`
3. This ensures that customers receive their confirmation emails automatically after 6 hours.

### Performance Optimization
1. Enable caching in Hostinger Control Panel
2. Configure CDN if available
3. Optimize images for web

### Security
1. Change default admin passwords
2. Enable two-factor authentication
3. Set up regular backups
4. Monitor error logs

## Troubleshooting

### Common Issues

#### 500 Internal Server Error
- Check file permissions (should be 755 for folders, 644 for files)
- Verify `.htaccess` syntax
- Check PHP error logs in Hostinger Control Panel

#### Database Connection Error
- Verify database credentials in `config_hostinger.php`
- Check if database user has proper permissions
- Ensure database exists

#### Images Not Uploading
- Check `assets/uploads/` folder permissions (755)
- Verify PHP upload limits in Hostinger Control Panel
- Check available disk space

#### White Screen
- Enable PHP error reporting temporarily
- Check syntax errors in PHP files
- Verify all required files are uploaded

### Error Logs Location
In Hostinger Control Panel: **Statistics** > **Error Logs**

## Post-Deployment Checklist

- [ ] Website loads correctly
- [ ] All pages are accessible
- [ ] Khinat products display properly
- [ ] Admin panel functions
- [ ] Image upload works
- [ ] File sharing links work
- [ ] Multi-language switching works
- [ ] Mobile responsiveness works
- [ ] SSL certificate is active
- [ ] Contact form works
- [ ] Cart functionality works
- [ ] Checkout process works

## Maintenance

### Regular Tasks
1. Update products and inventory
2. Monitor error logs
3. Backup database regularly
4. Update security patches
5. Monitor website performance

### Backups
1. Set up automatic daily backups via Hostinger
2. Download backups periodically
3. Test backup restoration process

## Support

If you encounter issues:
1. Check Hostinger Knowledge Base
2. Contact Hostinger Support
3. Review error logs
4. Test on staging environment first

---

**Your Meda e-commerce website is now ready for production on Hostinger!**
