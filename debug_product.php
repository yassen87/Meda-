<?php
require_once __DIR__ . '/includes/db.php';
$pdo = medal_pdo();
$st = $pdo->query("SELECT id, name_ar, primary_image_key FROM products ORDER BY id DESC LIMIT 5");
echo "<pre>";
print_r($st->fetchAll());
echo "</pre>";
