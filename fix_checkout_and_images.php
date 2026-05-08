<?php
// Fix script for checkout and image issues
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إصلاح مشاكل Checkout والصور</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #d4af37; text-align: center; margin-bottom: 30px; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .error { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { color: #388e3c; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #f57c00; background: #fff3e0; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .code { background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; direction: ltr; text-align: left; }
        .btn { background: #d4af37; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px 5px; }
        .btn:hover { background: #b8941f; }
        ul { text-align: right; }
        li { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>إصلاح مشاكل Checkout والصور</h1>
        
        <?php
        // 1. Check database connection
        echo "<div class='section'>";
        echo "<h2>🔍 فحص الاتصال بقاعدة البيانات</h2>";
        
        require_once __DIR__ . '/includes/db.php';
        $pdo = medal_pdo();
        
        if ($pdo) {
            echo "<div class='success'>✓ الاتصال بقاعدة البيانات: نجح</div>";
            
            // Check essential tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $required_tables = ['orders', 'order_items', 'products', 'clients', 'shipping_cities'];
            
            foreach ($required_tables as $table) {
                if (in_array($table, $tables)) {
                    echo "<div class='success'>✓ جدول $table: موجود</div>";
                } else {
                    echo "<div class='error'>✗ جدول $table: غير موجود</div>";
                }
            }
            
            // Check orders table structure
            if (in_array('orders', $tables)) {
                try {
                    $columns = $pdo->query("DESCRIBE orders")->fetchAll(PDO::FETCH_COLUMN);
                    $required_columns = ['order_number', 'status', 'customer_name', 'customer_phone', 'total'];
                    
                    foreach ($required_columns as $col) {
                        if (in_array($col, $columns)) {
                            echo "<div class='success'>✓ عمود $col في orders: موجود</div>";
                        } else {
                            echo "<div class='error'>✗ عمود $col في orders: غير موجود</div>";
                        }
                    }
                } catch (Exception $e) {
                    echo "<div class='error'>✗ خطأ في فحص جدول orders: " . $e->getMessage() . "</div>";
                }
            }
        } else {
            echo "<div class='error'>✗ الاتصال بقاعدة البيانات: فشل</div>";
        }
        echo "</div>";
        
        // 2. Check file permissions and structure
        echo "<div class='section'>";
        echo "<h2>📁 فحص الملفات والصلاحيات</h2>";
        
        $upload_dirs = ['assets/uploads', 'assets/uploads/transfers'];
        foreach ($upload_dirs as $dir) {
            if (is_dir($dir)) {
                $writable = is_writable($dir);
                echo "<div class='" . ($writable ? 'success' : 'error') . "'>" . ($writable ? '✓' : '✗') . " مجلد $dir: " . ($writable ? "قابل للكتابة" : "غير قابل للكتابة") . "</div>";
            } else {
                echo "<div class='error'>✗ مجلد $dir: غير موجود</div>";
            }
        }
        
        // Check uploaded images
        $images = glob('assets/uploads/img_*');
        echo "<div class='success'>✓ عدد الصور المرفوعة: " . count($images) . "</div>";
        
        // Check CSS files
        $css_files = ['assets/css/style.css', 'assets/css/theme-daylight.css'];
        foreach ($css_files as $file) {
            if (file_exists($file)) {
                echo "<div class='success'>✓ $file: موجود</div>";
            } else {
                echo "<div class='error'>✗ $file: غير موجود</div>";
            }
        }
        echo "</div>";
        
        // 3. Test image URLs
        echo "<div class='section'>";
        echo "<h2>🖼️ اختبار روابط الصور</h2>";
        
        if ($pdo && in_array('products', $tables)) {
            try {
                $products = $pdo->query("SELECT id, name_en, primary_image_key FROM products LIMIT 5")->fetchAll();
                foreach ($products as $product) {
                    $imageKey = $product['primary_image_key'];
                    if ($imageKey) {
                        $imagePath = '';
                        if (str_starts_with($imageKey, 'img_') || str_contains($imageKey, '.')) {
                            $imagePath = 'assets/uploads/' . $imageKey;
                        } else {
                            $imagePath = 'assets/img/' . $imageKey . '.jpg';
                        }
                        
                        if (file_exists($imagePath)) {
                            echo "<div class='success'>✓ صورة المنتج {$product['id']}: $imagePath</div>";
                        } else {
                            echo "<div class='error'>✗ صورة المنتج {$product['id']}: $imagePath (غير موجودة)</div>";
                        }
                    }
                }
            } catch (Exception $e) {
                echo "<div class='error'>✗ خطأ في فحص صور المنتجات: " . $e->getMessage() . "</div>";
            }
        }
        echo "</div>";
        
        // 4. Create fixes
        echo "<div class='section'>";
        echo "<h2>🛠️ تطبيق الإصلاحات</h2>";
        
        // Fix 1: Create missing directories
        $dirs_to_create = ['assets/uploads/transfers', 'logs'];
        foreach ($dirs_to_create as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                echo "<div class='success'>✓ تم إنشاء مجلد: $dir</div>";
            }
        }
        
        // Fix 2: Create .htaccess for uploads
        $htaccess_content = "Options -Indexes\n<IfModule mod_php.c>\n    php_flag engine off\n</IfModule>";
        file_put_contents('assets/uploads/.htaccess', $htaccess_content);
        echo "<div class='success'>✓ تم تحديث .htaccess لمجلد uploads</div>";
        
        // Fix 3: Create database fix script
        $db_fix_sql = "
-- Fix for missing columns in orders table
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS transfer_image VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS transfer_type ENUM('full', 'partial') DEFAULT 'full',
ADD COLUMN IF NOT EXISTS transfer_amount DECIMAL(10,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS promo_code VARCHAR(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS shipping_cost DECIMAL(10,2) DEFAULT 0.00;

-- Fix for missing stock column in product_variants
ALTER TABLE product_variants 
ADD COLUMN IF NOT EXISTS stock INT DEFAULT 0;

-- Fix for missing admin_notes column in orders
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS admin_notes TEXT DEFAULT NULL;
        ";
        
        file_put_contents('fix_database.sql', $db_fix_sql);
        echo "<div class='success'>✓ تم إنشاء ملف fix_database.sql</div>";
        
        echo "</div>";
        ?>
        
        <div class="section">
            <h2>📋 خطوات الإصلاح</h2>
            
            <h3>1. إصلاح قاعدة البيانات</h3>
            <p>قم باستيراد ملف <code>fix_database.sql</code> في phpMyAdmin:</p>
            <ol>
                <li>سجل دخول إلى phpMyAdmin في Hostinger</li>
                <li>اختر قاعدة البيانات u868008675_zein77_1</li>
                <li>اضغط على "Import"</li>
                <li>اختر ملف fix_database.sql</li>
                <li>اضغط على "Go"</li>
            </ol>
            
            <h3>2. إصلاح مشكلة Checkout</h3>
            <p>بعد إصلاح قاعدة البيانات، يجب أن يعمل Checkout بشكل صحيح.</p>
            
            <h3>3. إصلاح مشكلة الصور</h3>
            <p>تم إنشاء المجلدات والصلاحيات المطلوبة. الصور يجب أن تعمل الآن.</p>
            
            <h3>4. اختبار</h3>
            <p>بعد تطبيق الإصلاحات:</p>
            <ul>
                <li>اختبر عملية الشراء (Checkout)</li>
                <li>تحقق من عرض الصور في صفحات المنتجات</li>
                <li>اختبر رفع صور التحويل البنكي</li>
            </ul>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="/" class="btn">🏠 الصفحة الرئيسية</a>
                <a href="/products.php" class="btn">🛍️ المنتجات</a>
                <a href="/checkout.php" class="btn">🛒 Checkout</a>
                <a href="/admin/" class="btn">⚙️ لوحة الإدارة</a>
            </div>
        </div>
        
        <div class="section">
            <h3>🔧 ملاحظات هامة</h3>
            <ul>
                <li><strong>خطأ "Could not save order":</strong> سببه عادةً أعمدة مفقودة في جدول orders</li>
                <li><strong>صور لا تعمل:</strong> سببه صلاحيات المجلدات أو مسارات غير صحيحة</li>
                <li><strong>قاعدة البيانات:</strong> تأكد من استيراد schema.sql بالكامل</li>
                <li><strong>صلاحيات المجلدات:</strong> يجب أن تكون 755 للمجلدات و 644 للملفات</li>
            </ul>
        </div>
    </div>
</body>
</html>
