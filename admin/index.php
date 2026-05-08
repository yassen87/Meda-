<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_dashboard');

$pdo = medal_pdo();
$counts = ['orders_pending' => 0, 'orders_total' => 0, 'products' => 0, 'messages_unread' => 0];
$recent = [];

if ($pdo !== null) {
    try {
        $counts['orders_pending'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','processing')")->fetchColumn();
        $counts['orders_total'] = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $counts['products'] = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $counts['messages_unread'] = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE read_at IS NULL')->fetchColumn();
        $recent = $pdo->query('SELECT id, order_number, status, customer_name, subtotal, created_at FROM orders ORDER BY created_at DESC LIMIT 8')->fetchAll();
    } catch (Throwable) {
    }
}

require __DIR__ . '/_layout_start.php';
?>

<h1><?= esc(t('admin_dashboard')) ?></h1>
<p class="admin-lead"><?= esc(t('admin_dashboard_lead')) ?></p>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_connect_error')) ?></div>
<?php endif; ?>

<div class="admin-stats">
    <div class="admin-stat">
        <div>
            <strong><?= (int) $counts['orders_pending'] ?></strong>
            <span><?= esc(t('admin_stat_active_orders')) ?></span>
        </div>
        <div class="admin-stat-icon-box" style="background:rgba(212,175,55,0.1); color:#d4af37;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
        </div>
    </div>
    <div class="admin-stat">
        <div>
            <strong><?= (int) $counts['orders_total'] ?></strong>
            <span><?= esc(t('admin_stat_total_orders')) ?></span>
        </div>
        <div class="admin-stat-icon-box" style="background:rgba(59,130,246,0.1); color:#3b82f6;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
        </div>
    </div>
    <div class="admin-stat">
        <div>
            <strong><?= (int) $counts['products'] ?></strong>
            <span><?= esc(t('admin_stat_products')) ?></span>
        </div>
        <div class="admin-stat-icon-box" style="background:rgba(16,185,129,0.1); color:#10b981;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
        </div>
    </div>
    <div class="admin-stat">
        <div>
            <strong><?= (int) $counts['messages_unread'] ?></strong>
            <span><?= esc(t('admin_stat_unread_messages')) ?></span>
        </div>
        <div class="admin-stat-icon-box" style="background:rgba(239,68,68,0.1); color:#ef4444;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        </div>
    </div>
</div>

<div class="admin-card" style="padding: 30px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <h2 style="margin:0; font-size:1.4rem; font-weight:700;"><?= esc(t('admin_recent_orders')) ?></h2>
        <a href="<?= esc(admin_url('orders.php')) ?>" class="admin-btn admin-btn--secondary admin-btn--sm">عرض الكل</a>
    </div>

    <?php if ($recent === []): ?>
        <p class="admin-muted"><?= esc(t('admin_no_orders_yet')) ?></p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?= esc(t('admin_th_order')) ?></th>
                        <th><?= esc(t('admin_th_customer')) ?></th>
                        <th><?= esc(t('admin_th_status')) ?></th>
                        <th><?= esc(t('admin_th_total')) ?></th>
                        <th><?= esc(t('admin_th_date')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><a href="<?= esc(admin_url('order_view.php?id=' . (int) $r['id'])) ?>" style="font-weight:700; text-decoration:none; color:var(--admin-gold);"><?= esc((string) $r['order_number']) ?></a></td>
                            <td><div style="font-weight:600;"><?= esc((string) $r['customer_name']) ?></div></td>
                            <td><span class="admin-badge admin-badge--<?= esc((string) $r['status']) ?>"><?= esc(admin_order_status_label((string) $r['status'])) ?></span></td>
                            <td><span style="font-weight:800;"><?= number_format((float) $r['subtotal'], 2) ?></span> <small><?= esc(t('currency')) ?></small></td>
                            <td style="color:#888; font-size:0.9em;"><?= esc((string) $r['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
