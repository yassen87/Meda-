<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$pdo = medal_pdo();
$orderId = (int) ($_GET['id'] ?? $_POST['order_id'] ?? $_GET['edit'] ?? 0);

// Handle order updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    admin_verify_csrf();
    
    switch ($_POST['action']) {
        case 'update_order':
            $customerName = trim($_POST['customer_name']);
            $customerPhone = trim($_POST['customer_phone']);
            $customerEmail = trim($_POST['customer_email']);
            $shippingAddress = trim($_POST['shipping_address']);
            $status = $_POST['status'];
            $adminNotes = trim($_POST['admin_notes']);
            
            $update = $pdo->prepare("
                UPDATE orders 
                SET customer_name = ?, customer_phone = ?, customer_email = ?, 
                    shipping_address = ?, status = ?, admin_notes = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $update->execute([$customerName, $customerPhone, $customerEmail, $shippingAddress, $status, $adminNotes, $orderId]);
            
            $_SESSION['success'] = 'تم تحديث بيانات الطلب بنجاح!';
            header('Location: order_management.php?id=' . $orderId);
            exit;
            
        case 'add_item':
            $productId  = (int) $_POST['product_id'];
            $variantId  = !empty($_POST['variant_id']) ? (int) $_POST['variant_id'] : null;
            $quantity   = max(1, (int) $_POST['quantity']);

            // Get product name
            $pst = $pdo->prepare('SELECT name_ar, name_en FROM products WHERE id = ?');
            $pst->execute([$productId]);
            $product = $pst->fetch();

            if ($product) {
                // Get variant price — if variantId given use it, else cheapest variant
                if ($variantId) {
                    $vst = $pdo->prepare('SELECT id, label_ar, price FROM product_variants WHERE id = ? AND product_id = ?');
                    $vst->execute([$variantId, $productId]);
                } else {
                    $vst = $pdo->prepare('SELECT id, label_ar, price FROM product_variants WHERE product_id = ? ORDER BY price ASC LIMIT 1');
                    $vst->execute([$productId]);
                }
                $variant = $vst->fetch();

                if (!$variant) {
                    $_SESSION['error'] = 'لا توجد أسعار لهذا المنتج.';
                    header('Location: order_management.php?id=' . $orderId);
                    exit;
                }

                $unitPrice = (float) $variant['price'];
                $lineTotal = $quantity * $unitPrice;
                $variantLabel = $variant['label_ar'] ?? null;
                $usedVariantId = (int) $variant['id'];

                $insert = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, variant_id, product_name_snapshot, variant_label_snapshot, qty, unit_price, line_total)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insert->execute([$orderId, $productId, $usedVariantId, $product['name_ar'], $variantLabel, $quantity, $unitPrice, $lineTotal]);

                // Update order total
                update_order_totals($pdo, $orderId);

                $_SESSION['success'] = 'تم إضافة المنتج للطلب!';
            } else {
                $_SESSION['error'] = 'المنتج غير موجود.';
            }
            header('Location: order_management.php?id=' . $orderId);
            exit;

        case 'add_internal_item':
            $internalProductId = (int) $_POST['internal_product_id'];
            $quantity = (int) $_POST['quantity'];
            
            $insert = $pdo->prepare("
                INSERT INTO order_internal_products (order_id, internal_product_id, quantity)
                VALUES (?, ?, ?)
            ");
            $insert->execute([$orderId, $internalProductId, $quantity]);
            
            $_SESSION['success'] = 'تم إضافة الهدية/المنتج الداخلي للطلب!';
            header('Location: order_management.php?id=' . $orderId);
            exit;
            
        case 'remove_item':
            $itemId = (int) $_POST['item_id'];
            $delete = $pdo->prepare("DELETE FROM order_items WHERE id = ?");
            $delete->execute([$itemId]);
            
            update_order_totals($pdo, $orderId);
            
            $_SESSION['success'] = 'تم حذف المنتج من الطلب!';
            header('Location: order_management.php?id=' . $orderId);
            exit;

        case 'remove_internal_item':
            $itemId = (int) $_POST['item_id'];
            $delete = $pdo->prepare("DELETE FROM order_internal_products WHERE id = ?");
            $delete->execute([$itemId]);
            
            $_SESSION['success'] = 'تم حذف المنتج الداخلي من الطلب!';
            header('Location: order_management.php?id=' . $orderId);
            exit;
    }
}

function update_order_totals(PDO $pdo, int $orderId): void
{
    $st = $pdo->prepare('SELECT SUM(line_total) FROM order_items WHERE order_id = ?');
    $st->execute([$orderId]);
    $subtotal = (float) $st->fetchColumn();
    
    // Get shipping cost
    $sst = $pdo->prepare('SELECT shipping_cost, discount_amount FROM orders WHERE id = ?');
    $sst->execute([$orderId]);
    $order = $sst->fetch();
    $shipping = (float) ($order['shipping_cost'] ?? 0);
    $discount = (float) ($order['discount_amount'] ?? 0);
    
    $total = $subtotal + $shipping - $discount;
    
    $u = $pdo->prepare('UPDATE orders SET subtotal = ?, total = ? WHERE id = ?');
    $u->execute([$subtotal, $total, $orderId]);
}

$order = null;
$items = [];
$internalItems = [];
if ($orderId > 0) {
    $st = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $st->execute([$orderId]);
    $order = $st->fetch();
    
    if ($order) {
        $it = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $it->execute([$orderId]);
        $items = $it->fetchAll();
        
        $iit = $pdo->prepare('
            SELECT oip.*, ip.name_ar, ip.name_en, ip.type 
            FROM order_internal_products oip 
            JOIN internal_products ip ON oip.internal_product_id = ip.id 
            WHERE oip.order_id = ?
        ');
        $iit->execute([$orderId]);
        $internalItems = $iit->fetchAll();
    }
}

// Load all products with their variants for the quick-add form
$allProducts = $pdo->query('SELECT id, name_ar, name_en, primary_image_key FROM products ORDER BY name_ar ASC')->fetchAll();
try {
    $allVariants = $pdo->query('SELECT id, product_id, label_ar, price, stock FROM product_variants ORDER BY sort_order ASC, id ASC')->fetchAll();
} catch (Throwable) {
    // stock column may not exist yet — fallback without it
    $allVariants = $pdo->query('SELECT id, product_id, label_ar, price, 0 as stock FROM product_variants ORDER BY sort_order ASC, id ASC')->fetchAll();
}
// Group variants by product_id for JS use
$variantsByProduct = [];
foreach ($allVariants as $v) {
    $variantsByProduct[(int)$v['product_id']][] = $v;
}
$allInternal = $pdo->query('SELECT id, name_ar, name_en, type FROM internal_products ORDER BY name_ar ASC')->fetchAll();

$pageTitle = 'تعديل الطلب #' . ($order ? $order['order_number'] : '');
require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <h1>تعديل الطلب <?= $order ? '#' . esc($order['order_number']) : '' ?></h1>
    <a href="order_view.php?id=<?= $orderId ?>" class="admin-btn admin-btn--secondary">عرض الطلب</a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="admin-notice" style="background:rgba(16,185,129,.15); color:#059669; padding:1rem; border-radius:8px; margin-bottom:1.5rem;">
        <?= esc($_SESSION['success']) ?>
        <?php unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (!$order): ?>
    <div class="admin-error">الطلب غير موجود.</div>
<?php else: ?>
    <div style="display:grid; grid-template-columns: 1fr 350px; gap: 2rem;">
        <div>
            <!-- Customer Info -->
            <div class="admin-card">
                <h2 style="margin-top:0;">بيانات العميل والشحن</h2>
                <form method="POST" class="admin-form">
                    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_order">
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div>
                            <label for="customer_name">الاسم</label>
                            <input type="text" id="customer_name" name="customer_name" value="<?= esc($order['customer_name']) ?>" required>
                        </div>
                        <div>
                            <label for="customer_phone">الهاتف</label>
                            <input type="text" id="customer_phone" name="customer_phone" value="<?= esc($order['customer_phone']) ?>" required>
                        </div>
                    </div>
                    
                    <div style="margin-top:1rem;">
                        <label for="customer_email">الإيميل</label>
                        <input type="email" id="customer_email" name="customer_email" value="<?= esc($order['customer_email']) ?>">
                    </div>

                    <div style="margin-top:1rem;">
                        <label for="shipping_address">العنوان بالتفصيل</label>
                        <textarea id="shipping_address" name="shipping_address" rows="3"><?= esc($order['shipping_address']) ?></textarea>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1rem;">
                        <div>
                            <label for="status">حالة الطلب</label>
                            <select id="status" name="status">
                                <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= esc(admin_order_status_label($s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top:1rem;">
                        <label for="admin_notes">ملاحظات داخلية (لا يراها العميل)</label>
                        <textarea id="admin_notes" name="admin_notes" rows="2"><?= esc($order['admin_notes'] ?? '') ?></textarea>
                    </div>

                    <div style="margin-top:1.5rem;">
                        <button type="submit" class="admin-btn admin-btn--primary">حفظ التغييرات</button>
                    </div>
                </form>
            </div>

            <!-- Items -->
            <div class="admin-card" style="margin-top:2rem;">
                <h2 style="margin-top:0;">المنتجات في الطلب</h2>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>الكمية</th>
                                <th>السعر</th>
                                <th>الإجمالي</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= esc($item['product_name_snapshot']) ?></td>
                                    <td><?= (int)$item['qty'] ?></td>
                                    <td><?= number_format((float)$item['unit_price'], 2) ?></td>
                                    <td><?= number_format((float)$item['line_total'], 2) ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('حذف المنتج من الطلب؟')">
                                            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                            <input type="hidden" name="action" value="remove_item">
                                            <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                            <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger">حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:1.5rem; background:var(--admin-nav-link-hover-bg); padding:1.5rem; border-radius:12px; border:1px solid var(--admin-card-border);">
                    <h3 style="margin-top:0; font-size:1rem; margin-bottom:1rem;">➕ إضافة منتج للطلب</h3>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                        <input type="hidden" name="action" value="add_item">
                        <div style="display:grid; grid-template-columns: 1fr 1fr 80px 100px; gap:12px; align-items:end;">
                            <div>
                                <label style="font-size:0.85rem; font-weight:600;">المنتج</label>
                                <select name="product_id" id="add-product-select" onchange="loadVariants(this.value)" required>
                                    <option value="">اختر منتجاً...</option>
                                    <?php foreach ($allProducts as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>"><?= esc($p['name_ar']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:0.85rem; font-weight:600;">الحجم / المتغير</label>
                                <select name="variant_id" id="add-variant-select" required>
                                    <option value="">اختر الحجم...</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:0.85rem; font-weight:600;">الكمية</label>
                                <input type="number" name="quantity" value="1" min="1" required>
                            </div>
                            <button type="submit" class="admin-btn admin-btn--primary" style="margin-top:0;">إضافة</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Internal Items / Gifts -->
            <div class="admin-card" style="margin-top:2rem;">
                <h2 style="margin-top:0;">الهدايا والمنتجات الداخلية</h2>
                <?php if (empty($internalItems)): ?>
                    <p class="admin-muted">لا يوجد هدايا مضافة لهذا الطلب.</p>
                <?php else: ?>
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>المنتج</th>
                                    <th>النوع</th>
                                    <th>الكمية</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($internalItems as $item): ?>
                                    <tr>
                                        <td><?= esc($item['name_ar']) ?></td>
                                        <td><span class="admin-badge"><?= esc($item['type']) ?></span></td>
                                        <td><?= (int)$item['quantity'] ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('حذف؟')">
                                                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                                <input type="hidden" name="action" value="remove_internal_item">
                                                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                                <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger">حذف</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div style="margin-top:1.5rem; background:#f0f7ff; padding:1.5rem; border-radius:8px;">
                    <h3 style="margin-top:0; font-size:1rem;">إضافة هدية / عينة</h3>
                    <form method="POST" class="admin-form" style="display:grid; grid-template-columns: 1fr 100px 120px; gap:1rem; align-items:end;">
                        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                        <input type="hidden" name="action" value="add_internal_item">
                        <div>
                            <label>المنتج الداخلي</label>
                            <select name="internal_product_id" required>
                                <option value="">اختر هدية...</option>
                                <?php foreach ($allInternal as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= esc($p['name_ar']) ?> (<?= esc($p['type']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>الكمية</label>
                            <input type="number" name="quantity" value="1" min="1" required>
                        </div>
                        <button type="submit" class="admin-btn admin-btn--secondary">إضافة هدية</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar / Totals -->
        <div class="admin-sidebar">
            <div class="admin-card">
                <h2 style="margin-top:0; font-size:1.1rem;">ملخص الحساب</h2>
                <div style="display:flex; justify-content:space-between; margin-bottom:.5rem;">
                    <span>المجموع الفرعي:</span>
                    <strong><?= number_format((float)$order['subtotal'], 2) ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:.5rem;">
                    <span>الشحن:</span>
                    <strong><?= number_format((float)$order['shipping_cost'], 2) ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:.5rem; color:#dc2626;">
                    <span>الخصم:</span>
                    <strong>-<?= number_format((float)($order['discount_amount'] ?? 0), 2) ?></strong>
                </div>
                <hr style="border:0; border-top:1px solid #eee; margin:1rem 0;">
                <div style="display:flex; justify-content:space-between; font-size:1.2rem; font-weight:bold;">
                    <span>الإجمالي:</span>
                    <span><?= number_format((float)$order['total'], 2) ?> <?= esc(t('currency')) ?></span>
                </div>
            </div>
            
            <div class="admin-card" style="margin-top:1.5rem;">
                <h2 style="margin-top:0; font-size:1.1rem;">بيانات إضافية</h2>
                <p><strong>رقم الطلب:</strong> <?= esc($order['order_number']) ?></p>
                <p><strong>التاريخ:</strong> <?= esc($order['created_at']) ?></p>
                <?php if ($order['promo_code']): ?>
                    <p><strong>كود الخصم:</strong> <span class="admin-badge"><?= esc($order['promo_code']) ?></span></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
// Variants data from PHP — keyed by product_id
const variantsByProduct = <?= json_encode($variantsByProduct ?? []) ?>;

function loadVariants(productId) {
    const select = document.getElementById('add-variant-select');
    select.innerHTML = '<option value="">اختر الحجم...</option>';

    if (!productId || !variantsByProduct[productId]) return;

    variantsByProduct[productId].forEach(v => {
        const opt = document.createElement('option');
        opt.value = v.id;
        const stock = parseInt(v.stock);
        const stockLabel = stock > 0 ? ` (متاح: ${stock})` : ' ⚠️ نفد';
        opt.textContent = `${v.label_ar} — ${parseFloat(v.price).toFixed(2)} ج.م${stockLabel}`;
        if (stock === 0) opt.style.color = '#dc2626';
        select.appendChild(opt);
    });

    // Auto-select first variant
    if (select.options.length > 1) select.selectedIndex = 1;
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
