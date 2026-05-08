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

// Get product statistics
$pdo = medal_pdo();
$products = [];

if ($pdo) {
    $query = "
        SELECT 
            p.id,
            p.name_en,
            p.name_ar,
            p.category,
            p.price,
            p.view_count,
            COUNT(oi.id) as order_count,
            COALESCE(SUM(oi.quantity), 0) as total_sold,
            COALESCE(SUM(oi.quantity * oi.price), 0) as total_revenue,
            p.created_at
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
        GROUP BY p.id
        ORDER BY p.view_count DESC, p.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    // Get overall statistics
    $statsQuery = "
        SELECT 
            COUNT(*) as total_products,
            SUM(view_count) as total_views,
            COUNT(DISTINCT category) as total_categories,
            AVG(view_count) as avg_views_per_product
        FROM products
    ";
    
    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->execute();
    $stats = $statsStmt->fetch();
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
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .filter-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .filter-btn:hover {
            background: #5a6268;
        }
        .filter-btn.active {
            background: #d4af37;
        }
        .products-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .products-table th {
            background: #d4af37;
            color: white;
            padding: 15px;
            text-align: right;
        }
        .products-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .products-table tr:hover {
            background: #f8f9fa;
        }
        .product-name {
            font-weight: bold;
            color: #333;
        }
        .product-category {
            font-size: 0.9em;
            color: #666;
            background: #f0f0f0;
            padding: 3px 8px;
            border-radius: 12px;
            display: inline-block;
            margin-top: 5px;
        }
        .view-count {
            font-weight: bold;
            color: #007bff;
            font-size: 1.1em;
        }
        .order-count {
            font-weight: bold;
            color: #28a745;
        }
        .revenue {
            font-weight: bold;
            color: #d4af37;
        }
        .no-views {
            color: #dc3545;
            font-size: 0.9em;
        }
        .performance-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-left: 10px;
        }
        .performance-high { background: #28a745; }
        .performance-medium { background: #ffc107; }
        .performance-low { background: #dc3545; }
        @media (max-width: 768px) {
            .filter-buttons {
                flex-direction: column;
            }
            .filter-btn {
                width: 100%;
                text-align: center;
            }
            .products-table {
                font-size: 0.8em;
            }
            .products-table th,
            .products-table td {
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
                <div class="stat-value"><?= number_format($stats['total_products'] ?? 0) ?></div>
                <div class="stat-label">Untitled</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_views'] ?? 0) ?></div>
                <div class="stat-label">Untitled</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['avg_views_per_product'] ?? 0, 1) ?></div>
                <div class="stat-label">Untitled</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_categories'] ?? 0) ?></div>
                <div class="stat-label">Untitled</div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-buttons">
                <a href="?sort=views" class="filter-btn <?= ($_GET['sort'] ?? 'views') === 'views' ? 'active' : '' ?>">Untitled</a>
                <a href="?sort=orders" class="filter-btn <?= ($_GET['sort'] ?? '') === 'orders' ? 'active' : '' ?>">Untitled</a>
                <a href="?sort=revenue" class="filter-btn <?= ($_GET['sort'] ?? '') === 'revenue' ? 'active' : '' ?>">Untitled</a>
                <a href="?sort=name" class="filter-btn <?= ($_GET['sort'] ?? '') === 'name' ? 'active' : '' ?>">Untitled</a>
                <a href="?sort=category" class="filter-btn <?= ($_GET['sort'] ?? '') === 'category' ? 'active' : '' ?>">Untitled</a>
            </div>
        </div>
        
        <!-- Products Table -->
        <div class="products-table">
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
                    <?php 
                    $sort = $_GET['sort'] ?? 'views';
                    $sortedProducts = $products;
                    
                    // Sort products based on selected criteria
                    switch ($sort) {
                        case 'orders':
                            usort($sortedProducts, function($a, $b) {
                                return $b['order_count'] - $a['order_count'];
                            });
                            break;
                        case 'revenue':
                            usort($sortedProducts, function($a, $b) {
                                return $b['total_revenue'] - $a['total_revenue'];
                            });
                            break;
                        case 'name':
                            usort($sortedProducts, function($a, $b) {
                                return strcmp($a['name_en'], $b['name_en']);
                            });
                            break;
                        case 'category':
                            usort($sortedProducts, function($a, $b) {
                                return strcmp($a['category'], $b['category']);
                            });
                            break;
                        case 'views':
                        default:
                            // Already sorted by views in query
                            break;
                    }
                    
                    foreach ($sortedProducts as $product): 
                        // Determine performance indicator
                        $performance = 'low';
                        if ($product['view_count'] > 50) $performance = 'high';
                        elseif ($product['view_count'] > 10) $performance = 'medium';
                    ?>
                        <tr>
                            <td>
                                <div class="product-name"><?= htmlspecialchars($product['name_en']) ?></div>
                                <div class="product-category"><?= htmlspecialchars($product['category']) ?></div>
                            </td>
                            <td>
                                <div class="view-count">
                                    <?= number_format($product['view_count']) ?>
                                    <span class="performance-indicator performance-<?= $performance ?>"></span>
                                </div>
                                <?php if ($product['view_count'] == 0): ?>
                                    <div class="no-views">Untitled</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="order-count"><?= number_format($product['order_count']) ?></div>
                            </td>
                            <td>
                                <div><?= number_format($product['total_sold']) ?></div>
                            </td>
                            <td>
                                <div class="revenue"><?= number_format($product['total_revenue'], 2) ?>Untitled</div>
                            </td>
                            <td><?= number_format($product['price'], 2) ?>Untitled</td>
                            <td><?= date('Y-m-d', strtotime($product['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 50px; color: #666;">
                Untitled
            </div>
        <?php endif; ?>
        
        <!-- Performance Legend -->
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h3>Untitled</h3>
            <div style="display: flex; gap: 20px; margin-top: 10px;">
                <div><span class="performance-indicator performance-high"></span> Untitled (>50)</div>
                <div><span class="performance-indicator performance-medium"></span> Untitled (10-50)</div>
                <div><span class="performance-indicator performance-low"></span> Untitled (<10)</div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/_layout_end.php'; ?>
</body>
</html>
