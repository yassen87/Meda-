<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_products');

$pdo = medal_pdo();
$rows = [];
$db_error = '';

if ($pdo !== null) {
    try {
        // Try to fetch products with all columns
        $rows = $pdo->query('SELECT * FROM products ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (Throwable $e) {
        $db_error = $e->getMessage();
        $rows = [];
    }
}

require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <div>
        <h1><?= esc(t('admin_products')) ?></h1>
        <p class="admin-lead" style="margin-bottom:0"><?= esc(t('admin_products_lead')) ?></p>
    </div>
    <div class="admin-actions">
        <a class="btn-admin" href="<?= esc(admin_url('product_edit.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 0.5rem;"><path d="M12 5v14M5 12h14"/></svg>
            <?= esc(t('admin_new_product')) ?>
        </a>
    </div>
</div>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php elseif ($db_error !== ''): ?>
    <div class="admin-error">
        <p>حدث خطأ في قاعدة البيانات:</p>
        <code><?= esc($db_error) ?></code>
        <p style="margin-top:1rem">تأكد من استيراد ملف <code>database/schema.sql</code> في قاعدة البيانات الجديدة.</p>
    </div>
<?php elseif ($rows === []): ?>
    <div class="admin-card" style="text-align:center; padding:3rem">
        <p class="admin-muted"><?= esc(t('admin_no_products')) ?></p>
        <a class="btn-admin" href="<?= esc(admin_url('product_edit.php')) ?>"><?= esc(t('admin_new_product')) ?></a>
    </div>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px"><?= esc(t('admin_th_id')) ?></th>
                    <th style="width: 80px">الصورة</th>
                    <th><?= esc(t('admin_th_name_en')) ?></th>
                    <th><?= esc(t('admin_th_category')) ?></th>
                    <th><?= esc(t('admin_th_flags')) ?></th>
                    <th style="width: 80px">المخزون</th>
                    <th style="width: 80px">المشاهدات</th>
                    <th style="width: 100px"><?= esc(t('admin_th_active')) ?></th>
                    <th style="width: 100px"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td data-label="<?= esc(t('admin_th_id')) ?>"><span class="admin-muted">#<?= (int) $r['id'] ?></span></td>
                        <td data-label="الصورة">
                            <?php 
                            $imgStyle = product_image_style($r['primary_image_key']);
                            // Extract URL from style if it exists, or use default
                            $bgImg = 'none';
                            if (preg_match('/url\(\'(.*?)\'\)/', $imgStyle, $matches)) {
                                $bgImg = "url('" . $matches[1] . "')";
                            }
                            ?>
                            <div class="admin-thumb" style="width:48px; height:48px; border-radius:10px; background-color:var(--admin-page-bg); background-image:<?= $bgImg ?>; background-size:cover; background-position:center; border:1px solid var(--admin-table-border); box-shadow:var(--admin-shadow-sm);"></div>
                        </td>
                        <td data-label="<?= esc(t('admin_th_name_en')) ?>">
                            <div style="font-weight:600; color:var(--admin-heading)"><?= esc((string) $r['name_en']) ?></div>
                            <div class="admin-muted" style="font-size:0.8rem"><?= esc((string) $r['slug']) ?></div>
                        </td>
                        <td data-label="<?= esc(t('admin_th_category')) ?>">
                            <span style="background:var(--admin-nav-link-hover-bg); padding:0.2rem 0.5rem; border-radius:4px; font-size:0.85rem"><?= esc((string) $r['category']) ?></span>
                        </td>
                        <td data-label="<?= esc(t('admin_th_flags')) ?>">
                            <?php if (!empty($r['is_bestseller'])): ?>
                                <span class="admin-badge admin-badge--pending" style="font-size:0.7rem"><?= esc(t('admin_flag_bestseller')) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($r['is_offer'])): ?>
                                <span class="admin-badge admin-badge--processing" style="font-size:0.7rem"><?= esc(t('admin_flag_offer')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-label="المخزون">
                            <?php 
                            $totalStock = 0;
                            if ($pdo !== null) {
                                try {
                                    $stk = $pdo->prepare('SELECT SUM(stock) as total FROM product_variants WHERE product_id = ?');
                                    $stk->execute([(int)$r['id']]);
                                    $totalStock = (int)($stk->fetch()['total'] ?? 0);
                                } catch (Throwable $e) {
                                    $totalStock = 'N/A';
                                }
                            }
                            ?>
                            <span style="font-weight:700; color:#000; font-size:1rem;">
                                <?= $totalStock ?>
                            </span>
                        </td>
                        <td data-label="المشاهدات">
                            <span style="display:inline-flex;align-items:center;gap:0.3rem;font-size:0.9rem;color:#000;font-weight:600">
                                👁️ <?= (int) ($r['view_count'] ?? 0) ?>
                            </span>
                        </td>
                        <td data-label="<?= esc(t('admin_th_active')) ?>">
                            <?php if (!empty($r['active'])): ?>
                                <span class="admin-badge admin-badge--delivered" style="font-size:0.7rem"><?= esc(t('admin_yes')) ?></span>
                            <?php else: ?>
                                <span class="admin-badge admin-badge--cancelled" style="font-size:0.7rem"><?= esc(t('admin_no')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex; gap:0.5rem; justify-content:flex-end">
                                <a class="btn-admin btn-admin--sm" href="<?= esc(admin_url('product_edit.php?id=' . (int) $r['id'])) ?>" title="<?= esc(t('admin_edit')) ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<style>
.admin-thumb:not([style*="background-image"]) {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
.admin-thumb:not([style*="background-image"])::after {
    content: "No Image";
    font-size: 0.6rem;
    color: var(--admin-text-faint);
    text-align: center;
}
</style>

<?php require __DIR__ . '/_layout_end.php'; ?>
