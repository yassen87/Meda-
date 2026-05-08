<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pdo = medal_pdo();
$id = (int) ($_GET['id'] ?? $_POST['order_id'] ?? 0);

if ($pdo !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $id = (int) ($_POST['order_id'] ?? $id);
    $action = $_POST['action'] ?? '';
    if ($action === 'update' && $id > 0) {
        $status = (string) ($_POST['status'] ?? '');
        $notes = trim((string) ($_POST['admin_notes'] ?? ''));
        if (in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
            // Delete transfer image if status is changed to delivered
            if ($status === 'delivered') {
                $st = $pdo->prepare('SELECT transfer_image FROM orders WHERE id = ?');
                $st->execute([$id]);
                $oldImg = $st->fetchColumn();
                if ($oldImg) {
                    $path = __DIR__ . '/../assets/uploads/transfers/' . $oldImg;
                    if (is_file($path)) {
                        unlink($path);
                    }
                    $u = $pdo->prepare('UPDATE orders SET status = ?, admin_notes = ?, transfer_image = NULL WHERE id = ?');
                    $u->execute([$status, $notes !== '' ? $notes : null, $id]);
                } else {
                    $u = $pdo->prepare('UPDATE orders SET status = ?, admin_notes = ? WHERE id = ?');
                    $u->execute([$status, $notes !== '' ? $notes : null, $id]);
                }
            } else {
                $u = $pdo->prepare('UPDATE orders SET status = ?, admin_notes = ? WHERE id = ?');
                $u->execute([$status, $notes !== '' ? $notes : null, $id]);
            }
        }
        header('Location: ' . admin_url('order_view.php?id=' . $id));
        exit;
    }
}

$pageTitle = t('admin_order_title');
$order = null;
$items = [];

if ($pdo !== null && $id > 0) {
    $st = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $st->execute([$id]);
    $order = $st->fetch();
    if ($order !== false) {
        $it = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC');
        $it->execute([$id]);
        $items = $it->fetchAll();
    }
}

if ($order === null || $order === false) {
    http_response_code(404);
    $pageTitle = t('admin_page_not_found');
    require __DIR__ . '/_layout_start.php';
    echo '<p>' . esc(t('admin_order_not_found')) . '</p>';
    require __DIR__ . '/_layout_end.php';
    exit;
}

$pageTitle = t('admin_order_title') . ' ' . (string) $order['order_number'];
require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <h1><?= esc(t('admin_order_title')) ?> <?= esc((string) $order['order_number']) ?></h1>
    <a href="order_management.php?id=<?= (int)$id ?>" class="admin-btn admin-btn--primary">📝 تعديل الطلب</a>
</div>
<p class="admin-lead"><?= esc(t('admin_order_placed')) ?> <?= esc((string) $order['created_at']) ?></p>

<div class="admin-card">
    <h2 style="margin-top:0;font-size:1.05rem"><?= esc(t('admin_customer_section')) ?></h2>
    <p><strong><?= esc(t('admin_label_name')) ?>:</strong> <?= esc((string) $order['customer_name']) ?><br>
    <strong><?= esc(t('admin_label_email')) ?>:</strong> <a href="mailto:<?= esc((string) $order['customer_email']) ?>"><?= esc((string) $order['customer_email']) ?></a><br>
    <?php if (!empty($order['customer_phone'])): ?>
        <strong><?= esc(t('admin_label_phone')) ?>:</strong> <?= esc((string) $order['customer_phone']) ?><br>
    <?php endif; ?>
    <?php if (!empty($order['transfer_type'])): ?>
        <strong><?= esc(current_lang() === 'ar' ? 'نوع الدفع' : 'Payment Type') ?>:</strong> 
        <?php
            $type = $order['transfer_type'];
            if ($type === 'shipping') echo (current_lang() === 'ar' ? 'شحن فقط' : 'Shipping Only');
            elseif ($type === 'full') echo (current_lang() === 'ar' ? 'المبلغ كامل' : 'Full Amount');
            elseif ($type === 'partial') echo (current_lang() === 'ar' ? 'جزء من المبلغ' : 'Partial Amount') . ' (' . esc((string)$order['transfer_amount']) . ')';
        ?><br>
    <?php endif; ?>
    <strong><?= esc(t('admin_label_address')) ?>:</strong><br><?= nl2br(esc((string) $order['shipping_address'])) ?><br>
    <?php if (!empty($order['address_landmark'])): ?>
        <strong><?= esc(t('checkout_landmark')) ?>:</strong> <?= esc((string) $order['address_landmark']) ?><br>
    <?php endif; ?>
    <?= esc((string) $order['city']) ?>
    </p>
</div>

<?php if (!empty($order['transfer_image'])): ?>
<div class="admin-card">
    <h2 style="margin-top:0;font-size:1.05rem"><?= esc(current_lang() === 'ar' ? 'صورة التحويل' : 'Transfer Proof') ?></h2>
    <a href="<?= esc(storefront_url('assets/uploads/transfers/' . $order['transfer_image'])) ?>" target="_blank">
        <img src="<?= esc(storefront_url('assets/uploads/transfers/' . $order['transfer_image'])) ?>" alt="Transfer Proof" style="max-width:100%; max-height:400px; border-radius:8px; border:1px solid var(--border);">
    </a>
    <p class="admin-muted" style="margin-top:0.5rem"><?= esc(current_lang() === 'ar' ? 'سيتم حذف هذه الصورة تلقائياً عند تغيير حالة الطلب إلى "تم التوصيل"' : 'This image will be deleted automatically when the status is changed to "Delivered"') ?></p>
</div>
<?php endif; ?>

<div class="admin-card">
    <h2 style="margin-top:0;font-size:1.05rem"><?= esc(t('admin_line_items')) ?></h2>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th><?= esc(t('admin_th_product')) ?></th><th><?= esc(t('admin_th_variant')) ?></th><th><?= esc(t('admin_th_qty')) ?></th><th><?= esc(t('admin_th_unit')) ?></th><th><?= esc(t('admin_th_line')) ?></th></tr></thead>
            <tbody>
                <?php foreach ($items as $row): ?>
                    <tr>
                        <td><?= esc((string) $row['product_name_snapshot']) ?> <span class="admin-muted">#<?= (int) $row['product_id'] ?></span></td>
                        <td><?= esc((string) ($row['variant_label_snapshot'] ?? '')) ?></td>
                        <td><?= (int) $row['qty'] ?></td>
                        <td><?= number_format((float) $row['unit_price'], 2) ?> <?= esc(t('currency')) ?></td>
                        <td><?= number_format((float) $row['line_total'], 2) ?> <?= esc(t('currency')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p><strong><?= esc(t('admin_subtotal')) ?>:</strong> <?= number_format((float) $order['subtotal'], 2) ?> <?= esc(t('currency')) ?></p>
</div>

<div class="admin-card">
    <h2 style="margin-top:0;font-size:1.05rem"><?= esc(t('admin_fulfillment')) ?></h2>
    <form method="post" action="<?= esc(admin_url('order_view.php?id=' . $id)) ?>" class="admin-form">
        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
        <input type="hidden" name="order_id" value="<?= (int) $id ?>">
        <input type="hidden" name="action" value="update">
        <label for="status"><?= esc(t('admin_label_status')) ?></label>
        <select name="status" id="status">
            <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $s): ?>
                <option value="<?= esc($s) ?>"<?= $order['status'] === $s ? ' selected' : '' ?>><?= esc(admin_order_status_label($s)) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="admin_notes"><?= esc(t('admin_internal_notes')) ?></label>
        <textarea name="admin_notes" id="admin_notes" rows="4"><?= esc((string) ($order['admin_notes'] ?? '')) ?></textarea>
        <p style="margin-top:1rem"><button type="submit" class="btn-admin"><?= esc(t('admin_save')) ?></button></p>
    </form>
</div>

<p><a class="btn-admin" href="<?= esc(admin_url('orders.php')) ?>"><?= esc(t('admin_back_orders')) ?></a></p>

<?php require __DIR__ . '/_layout_end.php'; ?>
