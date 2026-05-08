<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = current_lang() === 'ar' ? 'إدارة العروض' : 'Manage Offers';

$pdo = medal_pdo();
$offers = [];

if ($pdo) {
    try {
        // Auto-create table if missing
        $pdo->exec("CREATE TABLE IF NOT EXISTS homepage_offers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            image_key VARCHAR(128) NOT NULL,
            link_url VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB");
        
        $st = $pdo->query("SELECT * FROM homepage_offers ORDER BY sort_order ASC");
        $offers = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
    }
}

// Ensure we have 8 slots
$slots = [];
for ($i = 0; $i < 8; $i++) {
    $slots[$i] = [
        'id' => null,
        'image_key' => '',
        'link_url' => '',
        'sort_order' => $i
    ];
}

foreach ($offers as $offer) {
    $idx = (int)$offer['sort_order'];
    if ($idx >= 0 && $idx < 8) {
        $slots[$idx] = $offer;
    }
}

require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-flex">
    <div>
        <h1><?= esc($pageTitle) ?></h1>
        <p class="admin-lead"><?= current_lang() === 'ar' ? 'يمكنك رفع حتى 8 صور للعروض لتظهر في الصفحة الرئيسية.' : 'You can upload up to 8 offer images to display on the homepage.' ?></p>
    </div>
</div>

<form class="admin-form" method="post" action="<?= esc(admin_url('offers_save.php')) ?>">
    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">

    <div class="offers-admin-grid">
        <?php for ($i = 0; $i < 8; $i++): 
            $s = $slots[$i];
            $hasImage = !empty($s['image_key']);
            $previewUrl = $hasImage ? url('assets/uploads/' . $s['image_key']) : '';
        ?>
            <div class="offer-slot-card" data-slot="<?= $i ?>">
                <div class="offer-slot-card__header">
                    <span class="slot-number">#<?= $i + 1 ?></span>
                </div>
                
                <div class="offer-slot-card__media">
                    <div class="image-preview-container <?= $hasImage ? 'has-image' : '' ?>" id="preview-<?= $i ?>">
                        <?php if ($hasImage): ?>
                            <img src="<?= esc($previewUrl) ?>" alt="Preview">
                        <?php else: ?>
                            <div class="placeholder">✦</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="upload-overlay">
                        <input type="file" id="file-<?= $i ?>" class="hidden-file-input" accept="image/*">
                        <button type="button" class="btn-upload-trigger" onclick="document.getElementById('file-<?= $i ?>').click()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        </button>
                    </div>
                </div>

                <div class="offer-slot-card__body">
                    <input type="hidden" name="offers[<?= $i ?>][image_key]" id="image-key-<?= $i ?>" value="<?= esc($s['image_key']) ?>">
                    <input type="hidden" name="offers[<?= $i ?>][sort_order]" value="<?= $i ?>">
                    
                    <label><?= current_lang() === 'ar' ? 'رابط العرض (اختياري)' : 'Offer Link (Optional)' ?></label>
                    <input type="text" name="offers[<?= $i ?>][link_url]" value="<?= esc($s['link_url']) ?>" placeholder="products.php?id=123" class="admin-input-sm">
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <div class="form-actions-fixed">
        <button type="submit" class="btn-admin btn-admin--lg"><?= esc(t('admin_save')) ?></button>
    </div>
</form>

<style>
.offers-admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 5rem;
}
.offer-slot-card {
    background: var(--admin-card-bg);
    border: 1px solid var(--admin-card-border);
    border-radius: var(--admin-radius-md);
    overflow: hidden;
    transition: all 0.3s ease;
}
.offer-slot-card:hover {
    box-shadow: var(--admin-shadow-md);
    border-color: var(--admin-nav-link-hover);
}
.offer-slot-card__header {
    padding: 0.75rem 1rem;
    background: rgba(212, 175, 55, 0.05);
    border-bottom: 1px solid var(--admin-card-border);
    display: flex;
    justify-content: space-between;
}
.slot-number {
    font-weight: 700;
    color: var(--admin-stat-num);
}
.offer-slot-card__media {
    aspect-ratio: 16 / 9;
    position: relative;
    background: #f9f9f9;
}
.dark .offer-slot-card__media { background: #080808; }

.image-preview-container {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.image-preview-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.image-preview-container .placeholder {
    font-size: 2rem;
    color: var(--admin-text-faint);
}
.upload-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.offer-slot-card:hover .upload-overlay {
    opacity: 1;
}
.hidden-file-input { display: none; }
.btn-upload-trigger {
    background: var(--admin-btn-bg);
    color: var(--admin-btn-text);
    border: none;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.offer-slot-card__body {
    padding: 1rem;
}
.offer-slot-card__body label {
    display: block;
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
    color: var(--admin-text-muted);
}
.admin-input-sm {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--admin-input-border);
    border-radius: 4px;
    background: var(--admin-input-bg);
    color: var(--admin-input-text);
}
.form-actions-fixed {
    position: fixed;
    bottom: 1.5rem;
    inset-inline-end: 2rem;
    z-index: 100;
}
.btn-admin--lg {
    padding: 1rem 3rem;
    font-size: 1.1rem;
    font-weight: 700;
    border-radius: 50px;
    box-shadow: 0 10px 20px rgba(212, 175, 55, 0.3);
}
</style>

<script>
document.querySelectorAll('.hidden-file-input').forEach(input => {
    input.addEventListener('change', async function() {
        if (!this.files.length) return;
        
        const slotIdx = this.id.split('-')[1];
        const formData = new FormData();
        formData.append('image', this.files[0]);
        
        const btn = this.nextElementSibling;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '...';
        btn.disabled = true;
        
        try {
            const response = await fetch('upload_handler.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('image-key-' + slotIdx).value = result.filename;
                const preview = document.getElementById('preview-' + slotIdx);
                preview.innerHTML = `<img src="${result.url}" alt="Preview">`;
                preview.classList.add('has-image');
            } else {
                alert('Upload failed: ' + (result.error || 'Unknown error'));
            }
        } catch (e) {
            alert('Upload error: ' + e.message);
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });
});
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
