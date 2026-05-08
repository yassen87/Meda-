<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_orders');

$pdo = medal_pdo();
$rows = [];
$filter = $_GET['status'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

if ($pdo !== null) {
    $sql = 'SELECT id, order_number, status, customer_name, customer_email, subtotal, created_at FROM orders WHERE 1=1';
    $params = [];
    
    if ($filter !== '' && in_array($filter, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
        $sql .= ' AND status = ?';
        $params[] = $filter;
    }
    
    if ($startDate !== '') {
        $sql .= ' AND created_at >= ?';
        $params[] = $startDate . ' 00:00:00';
    }
    
    if ($endDate !== '') {
        $sql .= ' AND created_at <= ?';
        $params[] = $endDate . ' 23:59:59';
    }
    
    $sql .= ' ORDER BY created_at DESC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
}

require __DIR__ . '/_layout_start.php';
?>

<h1><?= esc(t('admin_orders')) ?></h1>
<p class="admin-lead"><?= esc(t('admin_orders_lead')) ?></p>

<?php if (isset($_GET['msg'])): ?>
  <div class="admin-notice" style="padding:.75rem 1rem; border-radius:8px; margin-bottom:1rem;
       background:<?= $_GET['msg']==='email_sent' ? 'rgba(16,185,129,.15)' : 'rgba(239,68,68,.15)' ?>;
       color:<?= $_GET['msg']==='email_sent' ? '#059669' : '#dc2626' ?>;">
    <?= $_GET['msg']==='email_sent' ? '✅ تم إرسال إيميل التأكيد بنجاح.' : '❌ فشل إرسال الإيميل.' ?>
  </div>
<?php endif; ?>

<div class="admin-card" style="margin-bottom:1.5rem;">
    <form method="GET" class="admin-form" style="display:flex; gap:1rem; align-items:end; flex-wrap:wrap;">
        <div>
            <label for="start_date">من تاريخ</label>
            <input type="date" id="start_date" name="start_date" value="<?= esc($startDate) ?>">
        </div>
        <div>
            <label for="end_date">إلى تاريخ</label>
            <input type="date" id="end_date" name="end_date" value="<?= esc($endDate) ?>">
        </div>
        <div>
            <label for="status">الحالة</label>
            <select name="status" id="status">
                <option value=""><?= esc(t('admin_filter_all')) ?></option>
                <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $filter === $s ? 'selected' : '' ?>><?= esc(admin_order_status_label($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="admin-btn admin-btn--primary">بحث</button>
        <a href="<?= esc(admin_url('orders.php')) ?>" class="admin-btn admin-btn--secondary">إعادة تعيين</a>
    </form>
</div>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php elseif ($rows === []): ?>
    <p class="admin-muted"><?= esc(t('admin_no_orders')) ?></p>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr><th><?= esc(t('admin_th_order')) ?></th><th><?= esc(t('admin_th_customer')) ?></th><th><?= esc(t('admin_th_email')) ?></th><th><?= esc(t('admin_th_status')) ?></th><th><?= esc(t('admin_th_total')) ?></th><th><?= esc(t('admin_th_created')) ?></th><th>إيميل تأكيد</th><th>تعديل</th></tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td data-label="<?= esc(t('admin_th_order')) ?>"><a href="<?= esc(admin_url('order_view.php?id=' . (int) $r['id'])) ?>"><?= esc((string) $r['order_number']) ?></a></td>
                        <td data-label="<?= esc(t('admin_th_customer')) ?>"><?= esc((string) $r['customer_name']) ?></td>
                        <td data-label="<?= esc(t('admin_th_email')) ?>"><?= esc((string) $r['customer_email']) ?></td>
                        <td data-label="<?= esc(t('admin_th_status')) ?>"><span class="admin-badge admin-badge--<?= esc((string) $r['status']) ?>"><?= esc(admin_order_status_label((string) $r['status'])) ?></span></td>
                        <td data-label="<?= esc(t('admin_th_total')) ?>"><?= number_format((float) $r['subtotal'], 2) ?> <?= esc(t('currency')) ?></td>
                        <td data-label="<?= esc(t('admin_th_created')) ?>"><?= esc((string) $r['created_at']) ?></td>
                        <td>
                            <form method="POST" action="<?= esc(admin_url('send_order_email.php')) ?>" style="display:inline;">
                                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                <input type="hidden" name="order_id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" style="background:linear-gradient(135deg,#f0dc82,#d4af37); border:none; border-radius:6px; padding:.4rem .9rem; font-size:.78rem; font-weight:700; cursor:pointer; color:#1a1508;" title="إرسال إيميل تأكيد">
                                    📧 إرسال
                                </button>
                            </form>
                        </td>
                        <td>
                            <a href="<?= esc(admin_url('order_management.php?id=' . (int)$r['id'])) ?>" 
                               class="admin-btn admin-btn--sm admin-btn--primary"
                               title="تعديل الطلب - إضافة منتجات وهدايا وتغيير بيانات العميل">
                                ✏️ تعديل
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
