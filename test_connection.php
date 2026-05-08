<?php
// Test database connection script
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فحص الاتصال بقاعدة البيانات</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #d4af37; text-align: center; margin-bottom: 30px; }
        .result { padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #e8f5e8; color: #388e3c; border: 1px solid #4caf50; }
        .error { background: #ffebee; color: #d32f2f; border: 1px solid #f44336; }
        .info { background: #e3f2fd; color: #1976d2; border: 1px solid #2196f3; }
        .code { background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; direction: ltr; text-align: left; margin: 10px 0; }
        .btn { background: #d4af37; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px 5px; }
        .btn:hover { background: #b8941f; }
    </style>
</head>
<body>
    <div class="container">
        <h1>فحص الاتصال بقاعدة البيانات</h1>
        
        <?php
        // Test current configuration
        echo "<h2>الإعدادات الحالية</h2>";
        
        require_once __DIR__ . '/includes/db.php';
        
        echo "<div class='info'>";
        echo "<strong>DSN:</strong> " . (defined('MEDAL_DB_DSN') ? MEDAL_DB_DSN : 'Not defined') . "<br>";
        echo "<strong>User:</strong> " . (defined('MEDAL_DB_USER') ? MEDAL_DB_USER : 'Not defined') . "<br>";
        echo "<strong>Password:</strong> " . (defined('MEDAL_DB_PASS') ? '***' : 'Not defined') . "<br>";
        echo "</div>";
        
        echo "<h2>نتائج الاختبار</h2>";
        
        try {
            $pdo = medal_pdo();
            if ($pdo) {
                echo "<div class='success'>✓ الاتصال بقاعدة البيانات: نجح</div>";
                
                // Test basic query
                $version = $pdo->query("SELECT VERSION() as version")->fetch();
                echo "<div class='success'>✓ إصدار MySQL: " . $version['version'] . "</div>";
                
                // Check tables
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                echo "<div class='success'>✓ عدد الجداول: " . count($tables) . "</div>";
                
                if (in_array('admin_users', $tables)) {
                    $count = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
                    echo "<div class='success'>✓ مستخدمو الإدارة: $count</div>";
                } else {
                    echo "<div class='error'>✗ جدول admin_users غير موجود</div>";
                }
                
                if (in_array('products', $tables)) {
                    $count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                    echo "<div class='success'>✓ المنتجات: $count</div>";
                } else {
                    echo "<div class='error'>✗ جدول products غير موجود</div>";
                }
                
            } else {
                echo "<div class='error'>✗ الاتصال بقاعدة البيانات: فشل</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>✗ خطأ: " . $e->getMessage() . "</div>";
        }
        
        // Test Hostinger configuration
        echo "<h2>اختبار إعدادات Hostinger</h2>";
        
        try {
            $dsn = 'mysql:host=localhost;dbname=u868008675_zein77_1;charset=utf8mb4';
            $user = 'u868008675_zein12';
            $pass = '';
            
            $pdo_test = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            
            echo "<div class='success'>✓ اتصال Hostinger: نجح (محلياً)</div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>✗ اتصال Hostinger: فشل - " . $e->getMessage() . "</div>";
            echo "<div class='info'>ملاحظة: هذا الاختبار يعمل فقط عند تشغيله على خادم Hostinger</div>";
        }
        ?>
        
        <h2>الخطوات التالية</h2>
        
        <?php if (medal_pdo() !== null): ?>
            <div class="success">
                <p>✓ الاتصال بقاعدة البيانات يعمل بشكل صحيح!</p>
                <p>يمكنك الآن:</p>
                <ul>
                    <li><a href="/admin/setup.php" class="btn">تهيئة الإدارة</a></li>
                    <li><a href="/admin/login.php" class="btn">تسجيل دخول الإدارة</a></li>
                    <li><a href="/" class="btn">عرض الموقع</a></li>
                </ul>
            </div>
        <?php else: ?>
            <div class="error">
                <p>✗ يجب إصلاح مشاكل الاتصال أولاً</p>
                <p>الخطوات المقترحة:</p>
                <ol>
                    <li>التأكد من تشغيل MySQL/XAMPP</li>
                    <li>إنشاء قاعدة البيانات</li>
                    <li>استيراد ملف schema.sql</li>
                    <li>تشغيل setup_local_database.bat</li>
                </ol>
                <a href="setup_local_database.bat" class="btn">تشغيل إعداد قاعدة البيانات</a>
            </div>
        <?php endif; ?>
        
        <div class="info">
            <h3>معلومات مفيدة</h3>
            <p><strong>للتطوير المحلي:</strong> استخدم start_local.bat</p>
            <p><strong>للنشر على Hostinger:</strong> تم تحديث الإعدادات بالفعل</p>
            <p><strong>للاختبار:</strong> قم بزيارة debug_server.php</p>
        </div>
    </div>
</body>
</html>
