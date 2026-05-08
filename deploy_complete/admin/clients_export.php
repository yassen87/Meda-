<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$pdo = medal_pdo();
if (!$pdo) {
    exit('Database connection failed.');
}

// Get all clients (registered and guest)
$sql = "
    SELECT 
        c.name AS name,
        c.email AS email,
        c.phone AS phone,
        c.created_at AS created_at,
        (SELECT COUNT(*) FROM orders WHERE customer_email = c.email) AS order_count,
        (SELECT COALESCE(SUM(subtotal), 0) FROM orders WHERE customer_email = c.email) AS total_revenue
    FROM clients c

    UNION ALL

    SELECT 
        o.customer_name AS name,
        o.customer_email AS email,
        o.customer_phone AS phone,
        MIN(o.created_at) AS created_at,
        COUNT(*) AS order_count,
        SUM(o.subtotal) AS total_revenue
    FROM orders o
    WHERE o.customer_email NOT IN (SELECT email FROM clients)
    GROUP BY o.customer_email, o.customer_name, o.customer_phone

    ORDER BY created_at DESC
";

$clients = $pdo->query($sql)->fetchAll();

// CSV Generation
$filename = "Meda_Clients_" . date('Y-m-d_H-i') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header
fputcsv($output, ['الاسم', 'الإيميل', 'رقم الهاتف', 'عدد الطلبات', 'إجمالي الإيرادات', 'تاريخ التسجيل/أول طلب']);

// Data
foreach ($clients as $c) {
    fputcsv($output, [
        $c['name'],
        $c['email'],
        $c['phone'] ?: '-',
        $c['order_count'],
        $c['total_revenue'],
        $c['created_at']
    ]);
}

fclose($output);
exit;
