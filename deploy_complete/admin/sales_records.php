<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../includes/config.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get date filters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Build query
$where = [];
$params = [];

if ($start_date) {
    $where[] = "o.created_at >= ?";
    $params[] = $start_date . ' 00:00:00';
}

if ($end_date) {
    $where[] = "o.created_at <= ?";
    $params[] = $end_date . ' 23:59:59';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get sales records
$pdo = medal_pdo();
$sales = [];

if ($pdo) {
    $query = "
        SELECT 
            o.id as order_id,
            o.created_at,
            o.total_amount,
            o.status,
            o.customer_name,
            o.customer_phone,
            o.customer_email,
            o.customer_address,
            COUNT(oi.id) as item_count,
            GROUP_CONCAT(CONCAT(p.name_en, ' (', oi.quantity, 'x)') SEPARATOR ', ') as items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        $whereClause
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $sales = $stmt->fetchAll();
    
    // Get statistics
    $statsQuery = "
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value
        FROM orders o
        $whereClause
    ";
    
    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->execute($params);
    $stats = $statsStmt->fetch();
}

// Export to Excel functionality
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="sales_records_' . date('Y-m-d') . '.xls"');
    
    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
    echo "Order ID\tDate\tCustomer Name\tPhone\tEmail\tItems\tTotal Amount\tStatus\n";
    
    foreach ($sales as $sale) {
        echo $sale['order_id'] . "\t";
        echo date('Y-m-d H:i', strtotime($sale['created_at'])) . "\t";
        echo $sale['customer_name'] . "\t";
        echo $sale['customer_phone'] . "\t";
        echo $sale['customer_email'] . "\t";
        echo $sale['items'] . "\t";
        echo $sale['total_amount'] . "\t";
        echo $sale['status'] . "\n";
    }
    exit;
}

?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Untitled</title>
    <link rel="stylesheet" href="<?= admin_asset('assets/css/admin.css') ?>">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #d4af37, #b8941f);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            opacity: 0.9;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn-export {
            background: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-export:hover {
            background: #218838;
        }
        .sales-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .sales-table th {
            background: #d4af37;
            color: white;
            padding: 15px;
            text-align: right;
        }
        .sales-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .sales-table tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .order-details {
            max-width: 300px;
        }
        .customer-info {
            font-size: 0.9em;
            color: #666;
        }
        .items-list {
            max-width: 250px;
            font-size: 0.9em;
        }
        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
            }
            .filter-group {
                width: 100%;
            }
            .sales-table {
                font-size: 0.8em;
            }
            .sales-table th,
            .sales-table td {
                padding: 10px 5px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/_layout_start.php'; ?>
    
    <main class="admin-main">
        <div class="admin-header">
            <h1>Untitled</h1>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_orders'] ?? 0) ?></div>
                <div class="stat-label">Untitled</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_revenue'] ?? 0, 2) ?>Untitled</div>
                <div class="stat-label">Untitled</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['avg_order_value'] ?? 0, 2) ?>Untitled</div>
                <div class="stat-label">Untitled</div>
            </div>
        </div>
        
        <!-- Date Filter -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="start_date">Untitled</label>
                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="filter-group">
                    <label for="end_date">Untitled</label>
                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-export">Untitled</button>
                </div>
                <div class="filter-group">
                    <a href="?export=excel&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn-export">Untitled</a>
                </div>
            </form>
        </div>
        
        <!-- Sales Table -->
        <div class="sales-table">
            <table>
                <thead>
                    <tr>
                        <th>Untitled</th>
                        <th>Untitled</th>
                        <th>Untitled</th>
                        <th>Untitled</th>
                        <th>Untitled</th>
                        <th>Untitled</th>
                        <th>Untitled</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><?= $sale['order_id'] ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($sale['created_at'])) ?></td>
                            <td>
                                <div class="order-details">
                                    <div><strong><?= htmlspecialchars($sale['customer_name']) ?></strong></div>
                                    <div class="customer-info">
                                        <?= htmlspecialchars($sale['customer_phone']) ?><br>
                                        <?= htmlspecialchars($sale['customer_email']) ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="items-list">
                                    <?= htmlspecialchars($sale['items']) ?>
                                </div>
                            </td>
                            <td><?= number_format($sale['total_amount'], 2) ?>Untitled</td>
                            <td>
                                <span class="status-badge status-<?= $sale['status'] ?>">
                                    <?= htmlspecialchars($sale['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (empty($sales)): ?>
            <div style="text-align: center; padding: 50px; color: #666;">
                Untitled
            </div>
        <?php endif; ?>
    </main>
    
    <?php include __DIR__ . '/_layout_end.php'; ?>
</body>
</html>
