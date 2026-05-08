<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';
$pdo = medal_pdo();
if (!$pdo) die("No DB\n");

$pdo->exec("TRUNCATE TABLE shipping_cities");

$govs = [
    ['Cairo', 'القاهرة', 50],
    ['Giza', 'الجيزة', 50],
    ['Alexandria', 'الإسكندرية', 60],
    ['Port Said', 'بورسعيد', 60],
    ['Suez', 'السويس', 60],
    ['Ismailia', 'الإسماعيلية', 60],
    ['Dakahlia', 'الدقهلية', 60],
    ['Sharkia', 'الشرقية', 60],
    ['Gharbia', 'الغربية', 60],
    ['Menoufia', 'المنوفية', 60],
    ['Qalyubia', 'القليوبية', 60],
    ['Kafr El Sheikh', 'كفر الشيخ', 60],
    ['Damietta', 'دمياط', 60],
    ['Beheira', 'البحيرة', 60],
    ['Fayoum', 'الفيوم', 70],
    ['Beni Suef', 'بني سويف', 70],
    ['Minya', 'المنيا', 70],
    ['Assiut', 'أسيوط', 80],
    ['Sohag', 'سوهاج', 80],
    ['Qena', 'قنا', 80],
    ['Luxor', 'الأقصر', 90],
    ['Aswan', 'أسوان', 90],
    ['Red Sea', 'البحر الأحمر', 100],
    ['New Valley', 'الوادي الجديد', 100],
    ['Matrouh', 'مطروح', 100],
    ['North Sinai', 'شمال سيناء', 100],
    ['South Sinai', 'جنوب سيناء', 100],
];

$st = $pdo->prepare("INSERT INTO shipping_cities (name_en, name_ar, shipping_cost, sort_order) VALUES (?,?,?,?)");
$i = 10;
foreach($govs as $g) {
    $st->execute([$g[0], $g[1], $g[2], $i]);
    $i += 10;
}
echo "Egyptian Governorates added.\n";
