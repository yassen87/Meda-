<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
admin_verify_csrf();

$pdo = medal_pdo();
if ($pdo === null) {
    exit(t('admin_err_db_not_configured'));
}

$id = (int) ($_POST['id'] ?? 0);
$nameEn = trim((string) ($_POST['name_en'] ?? ''));
$slug = strtolower(trim((string) ($_POST['slug'] ?? '')));
if ($slug === '' && $nameEn !== '') {
    $slug = strtolower($nameEn);
    $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug);
    $slug = trim($slug, '-');
}
if ($slug === '') {
    $slug = 'product-' . time();
}

$category = strtolower(trim((string) ($_POST['category'] ?? 'unisex')));
$category = preg_replace('/[^a-z0-9\-]+/', '-', $category);
$category = trim((string) preg_replace('/-+/', '-', $category), '-');
if ($category === '') {
    $category = 'unisex';
}
$season = $_POST['season'] ?? 'both';
if (!in_array($season, ['winter', 'summer', 'both'], true)) {
    $season = 'both';
}

$active = isset($_POST['active']) ? 1 : 0;
$isBestseller = isset($_POST['is_bestseller']) ? 1 : 0;
$isOffer = isset($_POST['is_offer']) ? 1 : 0;
$sortOrder = (int) ($_POST['sort_order'] ?? 0);
$nameEn = trim((string) ($_POST['name_en'] ?? ''));
$nameAr = trim((string) ($_POST['name_ar'] ?? ''));
$notesEn = trim((string) ($_POST['notes_en'] ?? ''));
$notesAr = trim((string) ($_POST['notes_ar'] ?? ''));
$descEn = trim((string) ($_POST['description_en'] ?? ''));
$descAr = trim((string) ($_POST['description_ar'] ?? ''));
$primaryKey = trim((string) ($_POST['primary_image_key'] ?? 'default'));
if ($primaryKey === '') {
    $primaryKey = 'default';
}
$fileSharingUrl = trim((string) ($_POST['file_sharing_url'] ?? ''));
if ($fileSharingUrl !== '' && !filter_var($fileSharingUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    exit('Invalid file sharing URL format');
}

$variantRows = [];
foreach ($_POST['variants'] ?? [] as $row) {
    if (!is_array($row)) {
        continue;
    }
    $le = trim((string) ($row['label_en'] ?? ''));
    if ($le === '') {
        continue;
    }
    $la = trim((string) ($row['label_ar'] ?? ''));
    if ($la === '') {
        $la = $le;
    }
    $price = trim((string) ($row['price'] ?? ''));
    $p = $price === '' ? null : filter_var($price, FILTER_VALIDATE_FLOAT);
    if ($p === false || $p === null || $p < 0) {
        http_response_code(400);
        exit(t('admin_err_variant_price'));
    }
    $co = trim((string) ($row['compare_at_price'] ?? ''));
    $compare = $co === '' ? null : filter_var($co, FILTER_VALIDATE_FLOAT);
    if ($compare !== null && $compare === false) {
        $compare = null;
    }
    $variantRows[] = [
        'label_en' => $le,
        'label_ar' => $la,
        'price' => round((float) $p, 2),
        'compare_at_price' => $compare !== null ? round((float) $compare, 2) : null,
        'stock' => (int) ($row['stock'] ?? 0),
        'sort_order' => (int) ($row['sort_order'] ?? 0),
    ];
}

if ($variantRows === []) {
    http_response_code(400);
    exit(t('admin_err_variant_required'));
}

if ($nameEn === '' || $nameAr === '' || $descEn === '' || $descAr === '') {
    http_response_code(400);
    exit(t('admin_err_names_required'));
}

$galleryKeys = [];
$gt = trim((string) ($_POST['gallery_text'] ?? ''));
if ($gt !== '') {
    foreach (preg_split('/\r\n|\r|\n/', $gt) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $galleryKeys[] = $line;
        }
    }
} else {
    foreach ($_POST['gallery_keys'] ?? [] as $gk) {
        $gk = trim((string) $gk);
        if ($gk !== '') {
            $galleryKeys[] = $gk;
        }
    }
}
if ($galleryKeys === []) {
    $galleryKeys[] = $primaryKey;
}

try {
    $pdo->beginTransaction();

    if ($id > 0) {
        $chk = $pdo->prepare('SELECT id FROM products WHERE id = ?');
        $chk->execute([$id]);
        if ($chk->fetch() === false) {
            throw new RuntimeException(t('admin_err_product_not_found'));
        }
        $dupe = $pdo->prepare('SELECT id FROM products WHERE slug = ? AND id != ?');
        $dupe->execute([$slug, $id]);
        if ($dupe->fetch() !== false) {
            throw new RuntimeException(t('admin_err_slug_in_use'));
        }
        $u = $pdo->prepare(
            'UPDATE products SET slug=?, category=?, season=?, is_bestseller=?, is_offer=?, active=?,
             name_en=?, name_ar=?, notes_en=?, notes_ar=?, description_en=?, description_ar=?,
             primary_image_key=?, sort_order=?, file_sharing_url=? WHERE id=?'
        );
        $u->execute([
            $slug, $category, $season, $isBestseller, $isOffer, $active,
            $nameEn, $nameAr, $notesEn, $notesAr, $descEn, $descAr,
            $primaryKey, $sortOrder, $fileSharingUrl, $id,
        ]);
        $newId = $id;
    } else {
        $dupe = $pdo->prepare('SELECT id FROM products WHERE slug = ?');
        $dupe->execute([$slug]);
        if ($dupe->fetch() !== false) {
            throw new RuntimeException(t('admin_err_slug_in_use'));
        }
        $ins = $pdo->prepare(
            'INSERT INTO products (slug, category, season, is_bestseller, is_offer, active,
             name_en, name_ar, notes_en, notes_ar, description_en, description_ar, primary_image_key, sort_order, file_sharing_url)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $ins->execute([
            $slug, $category, $season, $isBestseller, $isOffer, $active,
            $nameEn, $nameAr, $notesEn, $notesAr, $descEn, $descAr,
            $primaryKey, $sortOrder, $fileSharingUrl,
        ]);
        $newId = (int) $pdo->lastInsertId();
    }

    $pdo->prepare('DELETE FROM product_variants WHERE product_id = ?')->execute([$newId]);
    try {
        $vins = $pdo->prepare(
            'INSERT INTO product_variants (product_id, label_en, label_ar, price, compare_at_price, sort_order, stock) VALUES (?,?,?,?,?,?,?)'
        );
        foreach ($variantRows as $vr) {
            $vins->execute([
                $newId,
                $vr['label_en'],
                $vr['label_ar'],
                $vr['price'],
                $vr['compare_at_price'],
                $vr['sort_order'],
                $vr['stock'],
            ]);
        }
    } catch (Throwable) {
        // stock column may not exist yet — save without it (run migrate.php to fix)
        $vins = $pdo->prepare(
            'INSERT INTO product_variants (product_id, label_en, label_ar, price, compare_at_price, sort_order) VALUES (?,?,?,?,?,?)'
        );
        foreach ($variantRows as $vr) {
            $vins->execute([
                $newId,
                $vr['label_en'],
                $vr['label_ar'],
                $vr['price'],
                $vr['compare_at_price'],
                $vr['sort_order'],
            ]);
        }
    }

    $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$newId]);
    $iins = $pdo->prepare('INSERT INTO product_images (product_id, image_key, sort_order) VALUES (?,?,?)');
    foreach (array_values($galleryKeys) as $i => $gk) {
        $iins->execute([$newId, $gk, $i]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}

header('Location: ' . admin_url('product_edit.php?id=' . $newId));
exit;
