<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
require_client();

$pdo = medal_pdo();
$orders = [];
if ($pdo) {
    try {
        $st = $pdo->prepare('SELECT * FROM orders WHERE customer_email = (SELECT email FROM clients WHERE id = ?) ORDER BY created_at DESC');
        $st->execute([client_id()]);
        $orders = $st->fetchAll();
    } catch (Throwable $e) {}
}

$pageTitle = t('client_dashboard');
?>
<!DOCTYPE html>
<html lang="<?= esc(current_lang()) ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?> — <?= esc(t('site_name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #d4af37;
            --primary-dark: #b8941f;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --text: #1a1a1a;
            --text-muted: #6b7280;
            --border: #e5e7eb;
        }
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            background: var(--card-bg);
            padding: 1.5rem 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--primary);
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .logout-btn {
            color: #ef4444;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .logout-btn:hover {
            background: #fef2f2;
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .order-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }
        .order-card:hover {
            border-color: var(--primary);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px dashed var(--border);
        }
        .order-number {
            font-weight: 700;
            color: var(--text);
        }
        .order-date {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .status-badge {
            padding: 0.35rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #e0e7ff; color: #3730a3; }
        .status-shipped { background: #d1fae5; color: #065f46; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        .detail-item label {
            display: block;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }
        .detail-item span {
            font-weight: 600;
            font-size: 0.95rem;
        }
        .order-total {
            color: var(--primary);
            font-size: 1.1rem;
            font-weight: 700;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--card-bg);
            border-radius: 16px;
            color: var(--text-muted);
        }
        .back-home {
            display: inline-block;
            margin-top: 1rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><?= esc(t('client_dashboard')) ?></h1>
            <div class="user-info">
                <span style="font-weight: 600;"><?= esc(client_name()) ?></span>
                <a href="logout.php" class="logout-btn"><?= esc(t('client_logout')) ?></a>
            </div>
        </header>

        <main>
            <h2 class="section-title">📦 <?= current_lang() === 'ar' ? 'طلباتي' : 'My Orders' ?></h2>

            <?php if ($orders === []): ?>
                <div class="empty-state">
                    <p><?= current_lang() === 'ar' ? 'لم تقم بإجراء أي طلبات بعد.' : 'You haven\'t placed any orders yet.' ?></p>
                    <a href="../products.php" class="back-home">← <?= current_lang() === 'ar' ? 'تصفح المنتجات' : 'Browse Products' ?></a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-number"><?= esc($o['order_number']) ?></div>
                                <div class="order-date"><?= date('Y-m-d H:i', strtotime($o['created_at'])) ?></div>
                            </div>
                            <span class="status-badge status-<?= esc($o['status']) ?>">
                                <?= esc(t('admin_status_' . $o['status']) ?? $o['status']) ?>
                            </span>
                        </div>
                        <div class="order-details">
                            <div class="detail-item">
                                <label><?= current_lang() === 'ar' ? 'إجمالي الطلب' : 'Total Amount' ?></label>
                                <span class="order-total"><?= number_format((float)$o['total'], 2) ?> <?= esc(t('currency')) ?></span>
                            </div>
                            <div class="detail-item">
                                <label><?= current_lang() === 'ar' ? 'طريقة الدفع' : 'Payment Method' ?></label>
                                <span>
                                    <?php
                                        $type = $o['transfer_type'] ?? 'full';
                                        if ($type === 'shipping') echo (current_lang() === 'ar' ? 'شحن فقط' : 'Shipping Only');
                                        elseif ($type === 'full') echo (current_lang() === 'ar' ? 'المبلغ كامل' : 'Full Amount');
                                        elseif ($type === 'partial') echo (current_lang() === 'ar' ? 'جزء من المبلغ' : 'Partial Amount');
                                    ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <label><?= current_lang() === 'ar' ? 'العنوان' : 'Shipping Address' ?></label>
                                <span style="font-size: 0.85rem;"><?= esc($o['city']) ?> - <?= esc($o['shipping_address']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>

        <footer style="margin-top: 4rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
            <a href="../index.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">← <?= current_lang() === 'ar' ? 'العودة للمتجر' : 'Back to Store' ?></a>
            <p style="margin-top: 1rem;">&copy; <?= date('Y') ?> <?= esc(t('site_name')) ?></p>
        </footer>
    </div>
</body>
</html>
<?php exit; ?>
