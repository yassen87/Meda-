<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_clients');

$pdo = medal_pdo();
$clients = [];
if ($pdo !== null) {
    try {
        // Correctly merge registered clients and guest customers from orders.
        // We calculate orders & subtotal per email, and also student count for registered clients.
        // Simplified query to ensure everyone shows up
        $sql = "
            SELECT 
                c.id AS client_id,
                c.name AS name,
                c.email AS email,
                c.phone AS phone,
                c.created_at AS created_at,
                (SELECT COUNT(*) FROM orders WHERE customer_email = c.email AND customer_email IS NOT NULL AND customer_email != '') AS order_count,
                (SELECT COALESCE(SUM(subtotal), 0) FROM orders WHERE customer_email = c.email AND customer_email IS NOT NULL AND customer_email != '') AS total_revenue,
                1 AS is_registered
            FROM clients c

            UNION ALL

            SELECT 
                NULL AS client_id,
                o.customer_name AS name,
                o.customer_email AS email,
                o.customer_phone AS phone,
                MIN(o.created_at) AS created_at,
                COUNT(*) AS order_count,
                SUM(o.subtotal) AS total_revenue,
                0 AS is_registered
            FROM orders o
            WHERE (o.customer_email IS NULL OR o.customer_email = '' OR o.customer_email NOT IN (SELECT email FROM clients WHERE email IS NOT NULL AND email != ''))
            GROUP BY o.customer_email, o.customer_phone, o.customer_name
            
            ORDER BY created_at DESC
        ";
        $clients = $pdo->query($sql)->fetchAll();
    } catch (Throwable $e) {
        $clients = [];
    }
}

require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <h1><?= esc(t('admin_clients')) ?></h1>
    <div class="admin-actions">
        <a href="clients_export.php" class="admin-btn admin-btn--secondary">📊 تصدير إكسل</a>
        <a href="client_edit.php" class="admin-btn admin-btn--primary"><?= esc(t('admin_new_client')) ?></a>
    </div>
</div>

<p class="admin-lead"><?= esc(t('admin_clients_lead')) ?></p>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php elseif ($clients === []): ?>
    <p class="admin-muted"><?= esc(t('admin_no_customers')) ?></p>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><?= esc(t('label_name')) ?></th>
                    <th><?= esc(t('label_email')) ?></th>
                    <th><?= esc(t('admin_th_phone')) ?></th>
                    <th><?= esc(t('admin_th_orders')) ?></th>
                    <th><?= esc(t('admin_th_revenue')) ?></th>
                    <th><?= esc(t('admin_th_status')) ?></th>
                    <th><?= esc(t('admin_th_actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $c): ?>
                    <tr>
                        <td data-label="<?= esc(t('label_name')) ?>">
                            <strong><?= esc((string)$c['name']) ?></strong>
                        </td>
                        <td data-label="<?= esc(t('label_email')) ?>"><?= esc((string)$c['email']) ?></td>
                        <td data-label="<?= esc(t('admin_th_phone')) ?>"><?= esc((string)$c['phone'] ?: '-') ?></td>
                        <td data-label="<?= esc(t('admin_th_orders')) ?>"><?= (int)$c['order_count'] ?></td>
                        <td data-label="<?= esc(t('admin_th_revenue')) ?>"><?= number_format((float)$c['total_revenue'], 2) ?> <?= esc(t('currency')) ?></td>
                        <td data-label="<?= esc(t('admin_th_status')) ?>">
                            <?php if ($c['is_registered']): ?>
                                <span class="admin-badge admin-badge--success"><?= esc(t('admin_nav_clients')) ?></span>
                            <?php else: ?>
                                <span class="admin-badge"><?= esc(t('admin_status_guest')) ?? 'Guest' ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?= esc(t('admin_th_actions')) ?>">
                            <div class="admin-actions">
                                <?php if ($c['is_registered']): ?>
                                    <a href="client_edit.php?id=<?= (int)$c['client_id'] ?>" class="admin-btn admin-btn--sm"><?= esc(t('admin_edit')) ?></a>
                                    <a href="students.php?client_id=<?= (int)$c['client_id'] ?>" class="admin-btn admin-btn--sm admin-btn--secondary"><?= esc(t('admin_students_manage')) ?></a>
                                <?php else: ?>
                                    <a href="client_edit.php?email=<?= urlencode((string)$c['email']) ?>&name=<?= urlencode((string)$c['name']) ?>" class="admin-btn admin-btn--sm admin-btn--primary">
                                        <?= esc(t('admin_register_client')) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
