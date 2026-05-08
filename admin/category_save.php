<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
admin_verify_csrf();

$pdo = medal_pdo();
if ($pdo === null) {
    exit(t('admin_err_db_not_configured'));
}

$id = (int) ($_POST['id'] ?? 0);
$slug = strtolower(trim((string) ($_POST['slug'] ?? '')));
$slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug);
$slug = trim((string) preg_replace('/-+/', '-', $slug), '-');
$nameEn = trim((string) ($_POST['name_en'] ?? ''));
$nameAr = trim((string) ($_POST['name_ar'] ?? ''));
$sortOrder = (int) ($_POST['sort_order'] ?? 0);

if ($slug === '' || $nameEn === '' || $nameAr === '') {
    http_response_code(400);
    exit(t('admin_err_invalid_input'));
}

if ($id > 0) {
    $dupe = $pdo->prepare('SELECT id FROM categories WHERE slug = ? AND id != ?');
    $dupe->execute([$slug, $id]);
    if ($dupe->fetch() !== false) {
        http_response_code(400);
        exit(t('admin_err_slug_in_use'));
    }
    $u = $pdo->prepare('UPDATE categories SET slug=?, name_en=?, name_ar=?, sort_order=? WHERE id=?');
    $u->execute([$slug, $nameEn, $nameAr, $sortOrder, $id]);
} else {
    $dupe = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
    $dupe->execute([$slug]);
    if ($dupe->fetch() !== false) {
        http_response_code(400);
        exit(t('admin_err_slug_in_use'));
    }
    $ins = $pdo->prepare('INSERT INTO categories (slug, name_en, name_ar, sort_order) VALUES (?,?,?,?)');
    $ins->execute([$slug, $nameEn, $nameAr, $sortOrder]);
}

header('Location: ' . admin_url('categories.php'));
exit;
