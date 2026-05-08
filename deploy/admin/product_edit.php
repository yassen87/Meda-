<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_new_product_title');

$pdo = medal_pdo();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$cats = [];
if ($pdo !== null) {
    try {
        $cats = $pdo->query('SELECT slug, name_en FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (Throwable) {
        $cats = [];
    }
}
if ($cats === []) {
    $cats = [
        ['slug' => 'women', 'name_en' => 'Women'],
        ['slug' => 'men', 'name_en' => 'Men'],
        ['slug' => 'unisex', 'name_en' => 'Unisex'],
    ];
}

$product = [
    'id' => 0,
    'slug' => '',
    'category' => 'unisex',
    'season' => 'both',
    'is_bestseller' => false,
    'is_offer' => false,
    'active' => true,
    'name_en' => '',
    'name_ar' => '',
    'notes_en' => '',
    'notes_ar' => '',
    'description_en' => '',
    'description_ar' => '',
    'primary_image_key' => 'default',
    'sort_order' => 0,
];
$variants = [
    ['label_en' => '50 ml', 'label_ar' => '50 مل', 'price' => '', 'compare_at_price' => '', 'stock' => 0, 'sort_order' => 0],
];
$galleryKeys = ['default'];

if ($pdo !== null && $id > 0) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if ($row !== false) {
        $pageTitle = t('admin_edit_product') . ': ' . (string) $row['name_en'];
        $product = [
            'id' => (int) $row['id'],
            'slug' => (string) $row['slug'],
            'category' => (string) $row['category'],
            'season' => (string) $row['season'],
            'is_bestseller' => !empty($row['is_bestseller']),
            'is_offer' => !empty($row['is_offer']),
            'active' => !empty($row['active']),
            'name_en' => (string) $row['name_en'],
            'name_ar' => (string) $row['name_ar'],
            'notes_en' => (string) ($row['notes_en'] ?? ''),
            'notes_ar' => (string) ($row['notes_ar'] ?? ''),
            'description_en' => (string) $row['description_en'],
            'description_ar' => (string) $row['description_ar'],
            'primary_image_key' => (string) $row['primary_image_key'],
            'sort_order' => (int) $row['sort_order'],
        ];
        $vst = $pdo->prepare('SELECT label_en, label_ar, price, compare_at_price, stock, sort_order FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, id ASC');
        $vst->execute([$id]);
        $vr = $vst->fetchAll();
        if ($vr !== []) {
            $variants = [];
            foreach ($vr as $v) {
                $variants[] = [
                    'label_en' => (string) $v['label_en'],
                    'label_ar' => (string) $v['label_ar'],
                    'price' => (string) $v['price'],
                    'compare_at_price' => $v['compare_at_price'] !== null ? (string) $v['compare_at_price'] : '',
                    'stock' => (int) ($v['stock'] ?? 0),
                    'sort_order' => (int) $v['sort_order'],
                ];
            }
        }
        $ist = $pdo->prepare('SELECT image_key FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC');
        $ist->execute([$id]);
        $imgs = $ist->fetchAll(PDO::FETCH_COLUMN);
        if ($imgs !== []) {
            $galleryKeys = array_map('strval', $imgs);
        }
    }
}

while (count($variants) < 5) {
    $variants[] = ['label_en' => '', 'label_ar' => '', 'price' => '', 'compare_at_price' => '', 'stock' => 0, 'sort_order' => count($variants)];
}

$galleryText = implode("\n", $galleryKeys);

require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
    <h1><?= $id > 0 ? 'تعديل المنتج' : 'إضافة منتج جديد' ?></h1>
    <a href="<?= esc(admin_url('products.php')) ?>" class="btn-admin btn-admin--secondary">العودة للمنتجات</a>
</div>

<form class="admin-form" method="post" action="<?= esc(admin_url('product_save.php')) ?>" id="product-form">
    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <!-- العمود الأيسر: البيانات الأساسية -->
        <div>
            <div class="admin-card" style="padding:1.5rem; margin-bottom:1.5rem;">
                <h2 style="margin-top:0; margin-bottom:1.5rem; font-size:1.1rem; border-bottom:1px solid #eee; padding-bottom:0.5rem;">البيانات الأساسية</h2>
                
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:bold;">اسم المنتج (بالعربي)</label>
                    <input type="text" name="name_ar" required value="<?= esc($product['name_ar']) ?>" dir="rtl" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:8px;">
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:bold;">اسم المنتج (بالإنجليزي)</label>
                    <input type="text" name="name_en" required value="<?= esc($product['name_en']) ?>" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:8px;">
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:bold;">وصف المنتج (بالعربي)</label>
                    <textarea name="description_ar" required dir="rtl" rows="4" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:8px;"><?= esc($product['description_ar']) ?></textarea>
                </div>

                <div style="display:none;">
                    <input type="hidden" name="slug" value="<?= esc($product['slug'] ?: 'product-' . time()) ?>">
                    <textarea name="description_en" style="display:none;"><?= esc($product['description_en'] ?: $product['description_ar']) ?></textarea>
                </div>
            </div>

            <div class="admin-card" style="padding:1.5rem;">
                <h2 style="margin-top:0; margin-bottom:1.5rem; font-size:1.1rem; border-bottom:1px solid #eee; padding-bottom:0.5rem;">الأحجام والأسعار والكمية</h2>
                <div class="variants-container">
                    <?php foreach ($variants as $i => $v): ?>
                        <div class="variant-row" style="background:#f9f9f9; padding:1.2rem; border-radius:12px; margin-bottom:1rem; border:1px solid #eee;">
                            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:1rem; align-items: end;">
                                <div>
                                    <label style="font-size:0.8rem; display:block; margin-bottom:0.3rem;">الحجم (مثلاً: 50 مل)</label>
                                    <input type="text" name="variants[<?= $i ?>][label_ar]" value="<?= esc($v['label_ar']) ?>" dir="rtl" style="width:100%; padding:0.6rem; border:1px solid #ddd; border-radius:6px;">
                                    <input type="hidden" name="variants[<?= $i ?>][label_en]" value="<?= esc($v['label_en'] ?: $v['label_ar']) ?>">
                                </div>
                                <div>
                                    <label style="font-size:0.8rem; display:block; margin-bottom:0.3rem;">السعر</label>
                                    <input type="text" name="variants[<?= $i ?>][price]" value="<?= esc($v['price']) ?>" placeholder="0.00" style="width:100%; padding:0.6rem; border:1px solid #ddd; border-radius:6px;">
                                </div>
                                <div>
                                    <label style="font-size:0.8rem; display:block; margin-bottom:0.3rem;">السعر قبل الخصم</label>
                                    <input type="text" name="variants[<?= $i ?>][compare_at_price]" value="<?= esc($v['compare_at_price']) ?>" placeholder="اختياري" style="width:100%; padding:0.6rem; border:1px solid #ddd; border-radius:6px;">
                                </div>
                                <div>
                                    <label style="font-size:0.8rem; display:block; margin-bottom:0.3rem; color:#e67e22; font-weight:bold;">الكمية المتوفرة</label>
                                    <input type="number" name="variants[<?= $i ?>][stock]" value="<?= (int) $v['stock'] ?>" style="width:100%; padding:0.6rem; border:1px solid #e67e22; border-radius:6px; font-weight:bold;">
                                </div>
                                <input type="hidden" name="variants[<?= $i ?>][sort_order]" value="<?= (int) $v['sort_order'] ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- العمود الأيمن: الصور والإعدادات -->
        <div>
            <div class="admin-card" style="padding:1.5rem; margin-bottom:1.5rem; border:2px solid #3498db; background:#f0f7ff;">
                <h2 style="margin-top:0; margin-bottom:1.5rem; font-size:1.1rem; color:#2980b9; display:flex; align-items:center; gap:0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    صور المنتج
                </h2>
                
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; margin-bottom:0.8rem; font-weight:bold;">الصورة الرئيسية</label>
                    <div style="position:relative; background:#fff; border:2px dashed #3498db; border-radius:12px; padding:1.5rem; text-align:center;">
                        <div id="image-preview" style="margin-bottom:1rem; <?= $product['primary_image_key'] !== 'default' ? '' : 'display:none;' ?>">
                            <img src="<?= esc(str_starts_with($product['primary_image_key'], 'http') ? $product['primary_image_key'] : '') ?>" style="max-width:100%; max-height:150px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                        </div>
                        <input type="text" id="primary_image_key" name="primary_image_key" value="<?= esc($product['primary_image_key']) ?>" style="display:none;">
                        <button type="button" class="btn-upload" data-target="primary_image_key" style="background:#3498db; color:white; border:none; padding:0.8rem 1.5rem; border-radius:50px; cursor:pointer; font-weight:bold; display:flex; align-items:center; gap:0.5rem; margin:0 auto;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            اختر صورة من جهازك
                        </button>
                        <p style="font-size:0.75rem; color:#7f8c8d; margin-top:0.8rem;">سيتم تحويل الصورة لرابط تلقائياً</p>
                    </div>
                </div>

                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:bold;">صور إضافية (اختياري)</label>
                    <textarea id="gallery" name="gallery_text" rows="3" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:8px; font-size:0.8rem; background:#f9f9f9;" placeholder="روابط الصور الإضافية هنا..."><?= esc($galleryText) ?></textarea>
                    <button type="button" class="btn-upload" data-target="gallery" style="width:100%; margin-top:0.5rem; background:#ecf0f1; color:#2c3e50; border:1px solid #bdc3c7; padding:0.5rem; border-radius:8px; cursor:pointer; font-size:0.9rem;">+ إضافة صور للمعرض</button>
                </div>
                <input type="file" id="image-upload-input" style="display:none;" accept="image/*">
            </div>

            <div class="admin-card" style="padding:1.5rem;">
                <h2 style="margin-top:0; margin-bottom:1.5rem; font-size:1.1rem; border-bottom:1px solid #eee; padding-bottom:0.5rem;">التصنيف والظهور</h2>
                
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:bold;">قسم المنتج</label>
                    <select name="category" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:8px; background:white;">
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= esc((string) $c['slug']) ?>"<?= $product['category'] === $c['slug'] ? ' selected' : '' ?>><?= esc((string) $c['name_en']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex; flex-direction:column; gap:0.8rem; padding:1rem; background:#fff9e6; border-radius:12px; border:1px solid #ffeaa7;">
                    <label style="cursor:pointer; display:flex; align-items:center; gap:0.5rem;"><input type="checkbox" name="active" value="1"<?= !empty($product['active']) ? ' checked' : '' ?>> تفعيل المنتج (يظهر في الموقع)</label>
                    <label style="cursor:pointer; display:flex; align-items:center; gap:0.5rem;"><input type="checkbox" name="is_bestseller" value="1"<?= !empty($product['is_bestseller']) ? ' checked' : '' ?>> تمييز كـ "الأكثر مبيعاً"</label>
                    <label style="cursor:pointer; display:flex; align-items:center; gap:0.5rem;"><input type="checkbox" name="is_offer" value="1"<?= !empty($product['is_offer']) ? ' checked' : '' ?>> تمييز كـ "عرض خاص"</label>
                </div>
                
                <div style="display:none;">
                    <select name="season"><option value="both" selected></option></select>
                    <input type="number" name="sort_order" value="<?= (int) $product['sort_order'] ?>">
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:3rem; padding:2rem; background:white; border-top:1px solid #eee; text-align:center; position:sticky; bottom:0; box-shadow:0 -5px 15px rgba(0,0,0,0.05); z-index:100;">
        <button type="submit" class="btn-admin" style="background:#27ae60; color:white; padding:1rem 4rem; font-size:1.2rem; border-radius:50px; cursor:pointer; border:none; box-shadow:0 4px 15px rgba(39, 174, 96, 0.3);">حفظ المنتج الآن</button>
        <?php if ($id > 0): ?>
            <button type="button" class="btn-admin btn-admin--danger" onclick="if(confirm('هل أنت متأكد من حذف هذا المنتج؟')) document.getElementById('delete-form').submit();" style="margin-inline-start:1rem;">حذف المنتج</button>
        <?php endif; ?>
    </div>
</form>

<?php if ($id > 0): ?>
    <form id="delete-form" method="post" action="<?= esc(admin_url('product_delete.php')) ?>" style="display:none">
        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
        <input type="hidden" name="delete_product" value="1">
    </form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadInput = document.getElementById('image-upload-input');
    const uploadButtons = document.querySelectorAll('.btn-upload');
    const previewContainer = document.getElementById('image-preview');
    let currentTarget = null;

    uploadButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            currentTarget = this.getAttribute('data-target');
            uploadInput.click();
        });
    });

    uploadInput.addEventListener('change', function() {
        if (!this.files || !this.files[0]) return;

        const file = this.files[0];
        const formData = new FormData();
        formData.append('image', file);

        const apiKey = '08169e6d0a797436d42171505342a38b'; 
        
        const btn = document.querySelector(`.btn-upload[data-target="${currentTarget}"]`);
        const originalHtml = btn.innerHTML;
        btn.innerHTML = 'جاري الرفع...';
        btn.disabled = true;

        fetch(`https://api.imgbb.com/1/upload?key=${apiKey}`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const url = data.data.url;
                const targetElement = document.getElementById(currentTarget);
                if (targetElement.tagName === 'TEXTAREA') {
                    const currentVal = targetElement.value.trim();
                    targetElement.value = currentVal ? currentVal + "\n" + url : url;
                } else {
                    targetElement.value = url;
                    if (currentTarget === 'primary_image_key' && previewContainer) {
                        previewContainer.style.display = 'block';
                        previewContainer.querySelector('img').src = url;
                    }
                }
            } else {
                alert('فشل رفع الصورة: ' + (data.error ? data.error.message : 'خطأ غير معروف'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء الاتصال بخدمة الرفع');
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            uploadInput.value = '';
        });
    });
});
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>