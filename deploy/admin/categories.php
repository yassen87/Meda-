<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_categories');

$pdo = medal_pdo();
$rows = [];
$edit = null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($pdo !== null) {
    $rows = $pdo->query('SELECT * FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll();
    if ($editId > 0) {
        $st = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $st->execute([$editId]);
        $edit = $st->fetch();
    }
}

require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <div>
        <h1><?= esc(t('admin_categories')) ?></h1>
        <p class="admin-lead" style="margin-bottom:0"><?= esc(t('admin_categories_lead')) ?></p>
    </div>
</div>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php else: ?>

<div class="admin-grid-two-cols" style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; align-items: start;">
    <div class="admin-card">
        <h2 style="margin-top:0;font-size:1.1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:0.5rem">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            <?= $edit ? esc(t('admin_edit_category')) : esc(t('admin_add_category')) ?>
        </h2>
        <form class="admin-form" method="post" action="<?= esc(admin_url('category_save.php')) ?>">
            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
            
            <div style="margin-bottom:1rem">
                <label for="slug"><?= esc(t('admin_label_slug')) ?></label>
                <input type="text" id="slug" name="slug" required value="<?= esc($edit ? (string) $edit['slug'] : '') ?>" pattern="[a-z0-9\-]+" placeholder="e.g. men-perfumes">
            </div>

            <div style="margin-bottom:1rem">
                <label for="name_en"><?= esc(t('admin_label_name_en')) ?></label>
                <input type="text" id="name_en" name="name_en" required value="<?= esc($edit ? (string) $edit['name_en'] : '') ?>" placeholder="English Name">
            </div>

            <div style="margin-bottom:1rem">
                <label for="name_ar"><?= esc(t('admin_label_name_ar')) ?></label>
                <input type="text" id="name_ar" name="name_ar" required value="<?= esc($edit ? (string) $edit['name_ar'] : '') ?>" dir="rtl" placeholder="الاسم بالعربي">
            </div>

            <div style="margin-bottom:1.5rem">
                <label for="sort_order"><?= esc(t('admin_label_sort_order')) ?></label>
                <input type="number" id="sort_order" name="sort_order" value="<?= $edit ? (int) $edit['sort_order'] : 0 ?>">
            </div>

            <div style="display:flex;gap:0.5rem">
                <button type="submit" class="btn-admin" style="flex:1"><?= esc(t('admin_save')) ?></button>
                <?php if ($edit): ?>
                    <a href="<?= esc(admin_url('categories.php')) ?>" class="btn-admin btn-admin--danger" style="text-align:center;text-decoration:none">إلغاء</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 25%"><?= esc(t('admin_label_slug')) ?></th>
                    <th><?= esc(t('admin_th_en')) ?></th>
                    <th><?= esc(t('admin_th_ar')) ?></th>
                    <th style="width: 10%"><?= esc(t('admin_th_sort')) ?></th>
                    <th style="width: 15%"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><code style="background:var(--admin-nav-link-hover-bg);padding:0.2rem 0.4rem;border-radius:4px;font-size:0.85rem"><?= esc((string) $r['slug']) ?></code></td>
                        <td style="font-weight:500"><?= esc((string) $r['name_en']) ?></td>
                        <td dir="rtl" style="font-family:'Tajawal', sans-serif"><?= esc((string) $r['name_ar']) ?></td>
                        <td style="text-align:center"><?= (int) $r['sort_order'] ?></td>
                        <td>
                            <div style="display:flex;gap:0.4rem;justify-content:flex-end">
                                <a class="btn-admin btn-admin--sm" href="<?= esc(admin_url('categories.php?edit=' . (int) $r['id'])) ?>" title="<?= esc(t('admin_edit')) ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <form method="post" action="<?= esc(admin_url('category_delete.php')) ?>" style="display:inline" onsubmit="return confirm(<?= admin_js_string('admin_confirm_delete_category') ?>);">
                                    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                    <button type="submit" class="btn-admin btn-admin--danger btn-admin--sm" title="<?= esc(t('admin_delete')) ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--admin-text-faint)">لا توجد تصنيفات حالياً.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@media (max-width: 992px) {
    .admin-grid-two-cols {
        grid-template-columns: 1fr !important;
    }
}
.btn-admin--sm {
    padding: 0.4rem !important;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>