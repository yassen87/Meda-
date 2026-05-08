<?php
// Fix script for server deployment issues
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إصلاح مشاكل الخادم - Meda</title>
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
        <h1>إصلاح مشاكل نشر Meda</h1>
        
        <div class="section">
            <h2>🔍 تشخيص المشاكل</h2>
            
            <?php
            // Check PHP version
            $php_version = PHP_VERSION;
            if (version_compare($php_version, '7.4.0', '>=')) {
                echo "<div class='success'>✓ إصدار PHP: $php_version (متوافق)</div>";
            } else {
                echo "<div class='error'>✗ إصدار PHP: $php_version (يتطلب 7.4 أو أعلى)</div>";
            }
            
            // Check required extensions
            $required = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
            foreach ($required as $ext) {
                if (extension_loaded($ext)) {
                    echo "<div class='success'>✓ الامتداد $ext: مثبت</div>";
                } else {
                    echo "<div class='error'>✗ الامتداد $ext: غير مثبت</div>";
                }
            }
            
            // Check directories
            $dirs = ['assets/uploads', 'tmp', 'logs'];
            foreach ($dirs as $dir) {
                if (is_dir($dir)) {
                    if (is_writable($dir)) {
                        echo "<div class='success'>✓ المجلد $dir: موجود وقابل للكتابة</div>";
                    } else {
                        echo "<div class='warning'>⚠ المجلد $dir: موجود ولكن غير قابل للكتابة</div>";
                    }
                } else {
                    echo "<div class='error'>✗ المجلد $dir: غير موجود</div>";
                }
            }
            
            // Check database connection
            echo "<h3>فحص قاعدة البيانات</h3>";
            try {
                require_once __DIR__ . '/includes/db.php';
                $pdo = medal_pdo();
                if ($pdo) {
                    echo "<div class='success'>✓ الاتصال بقاعدة البيانات: نجح</div>";
                    
                    // Check tables
                    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                    if (in_array('admin_users', $tables) && in_array('products', $tables)) {
                        echo "<div class='success'>✓ الجداول الأساسية: موجودة</div>";
                        
                        $admin_count = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
                        $product_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                        echo "<div class='success'>✓ $admin_count مستخدم إدارة، $product_count منتج</div>";
                    } else {
                        echo "<div class='error'>✗ الجداول الأساسية: غير موجودة</div>";
                    }
                } else {
                    echo "<div class='error'>✗ الاتصال بقاعدة البيانات: فشل</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>✗ خطأ في قاعدة البيانات: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
        
        <div class="section">
            <h2>🛠️ حلول المشاكل</h2>
            
            <h3>1. مشكلة خطأ 500 في صفحة الإدارة</h3>
            <p><strong>السبب:</strong> عادةً ما يكون بسبب إعدادات قاعدة البيانات غير الصحيحة أو عدم تهيئة قاعدة البيانات.</p>
            
            <h4>الحل:</h4>
            <ol>
                <li>تحديث إعدادات قاعدة البيانات في الملف <code>includes/db.local.php</code></li>
                <li>إنشاء قاعدة بيانات جديدة في Hostinger</li>
                <li>استيراد ملف Schema من مجلد <code>database/</code></li>
                <li>تشغيل سكريبت التهيئة</li>
            </ol>
            
            <div class="code">
# تحديث إعدادات قاعدة البيانات
# استبدل بالقيم الصحيحة من Hostinger
define('MEDAL_DB_DSN', 'mysql:host=localhost;dbname=your_db_name;charset=utf8mb4');
define('MEDAL_DB_USER', 'your_db_user');
define('MEDAL_DB_PASS', 'your_db_password');
            </div>
            
            <h3>2. مشكلة عرض المنتجات بدون تنسيق</h3>
            <p><strong>السبب:</strong> ملفات CSS غير محملة بشكل صحيح أو مسارات غير صحيحة.</p>
            
            <h4>الحل:</h4>
            <ol>
                <li>التأكد من رفع ملفات CSS إلى الخادم</li>
                <li>فحص مسارات ملفات CSS في ملف <code>.htaccess</code></li>
                <li>التأكد من صلاحيات المجلدات</li>
            </ol>
            
            <h3>3. إصلاح سريع</h3>
            <p>إذا كنت تريد حل سريع، قم بإنشاء ملف <code>includes/db.local.php</code> بالإعدادات الصحيحة:</p>
            
            <div class="code">
&lt;?php
declare(strict_types=1);

// استبدل هذه القيم بقيم قاعدة البيانات الصحيحة
define('MEDAL_DB_DSN', 'mysql:host=localhost;dbname=medal_db;charset=utf8mb4');
define('MEDAL_DB_USER', 'your_username');
define('MEDAL_DB_PASS', 'your_password');
            </div>
        </div>
        
        <div class="section">
            <h2>📋 خطوات النشر الصحيحة</h2>
            
            <ol>
                <li><strong>إنشاء قاعدة البيانات:</strong> من لوحة تحكم Hostinger</li>
                <li><strong>تحديث إعدادات قاعدة البيانات:</strong> في ملف <code>includes/db.local.php</code></li>
                <li><strong>استيراد قاعدة البيانات:</strong> من ملف <code>database/schema.sql</code></li>
                <li><strong>تشغيل التهيئة:</strong> زيارة <code>/admin/setup.php</code></li>
                <li><strong>فحص الصلاحيات:</strong> التأكد من أن المجلدات قابلة للكتابة</li>
                <li><strong>اختبار الموقع:</strong> التأكد من عمل جميع الصفحات</li>
            </ol>
        </div>
        
        <div class="section">
            <h2>🔧 روابط مفيدة</h2>
            <ul>
                <li><a href="/admin/setup.php" class="btn">تهيئة الإدارة</a></li>
                <li><a href="/debug_server.php" class="btn">فحص الخادم</a></li>
                <li><a href="/admin/login.php" class="btn">تسجيل دخول الإدارة</a></li>
            </ul>
        </div>
        
        <div class="section">
            <p><strong>ملاحظة:</strong> بعد تطبيق الإصلاحات، قم بمسح ذاكرة التخزين المؤقت للمتصفح وأعد تحديث الصفحة.</p>
        </div>
    </div>
</body>
</html>
