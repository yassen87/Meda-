<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = t('page_checkout');
$errors = [];
$done = false;
$orderNumber = '';
$shippingCities = [];
$pdoMain = medal_pdo();
if ($pdoMain !== null) {
    try {
        $shippingCities = $pdoMain->query('SELECT * FROM shipping_cities WHERE active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (\Throwable $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'submit_order';
    
    if ($action === 'remove') {
        $lineKey = (string) ($_POST['line_key'] ?? '');
        if ($lineKey !== '' && isset($_SESSION['cart'][$lineKey])) {
            remove_cart_line($lineKey);
        }
        header('Location: ' . url('checkout.php'));
        exit;
    }
    
    if ($action === 'update_qty') {
        $lineKey = (string) ($_POST['line_key'] ?? '');
        $newQty = (int) ($_POST['qty'] ?? 1);
        if ($lineKey !== '' && isset($_SESSION['cart'][$lineKey])) {
            if ($newQty > 0) {
                $_SESSION['cart'][$lineKey] = $newQty;
            } else {
                remove_cart_line($lineKey);
            }
        }
        header('Location: ' . url('checkout.php'));
        exit;
    }

    if ($action === 'submit_order') {
        $name = trim((string) ($_POST['customer_name'] ?? ''));
        $email = trim((string) ($_POST['customer_email'] ?? ''));
        $phone = trim((string) ($_POST['customer_phone'] ?? ''));
        $address = trim((string) ($_POST['shipping_address'] ?? ''));
        $cityId = (int) ($_POST['city_id'] ?? 0);
        $cityName = '';
        $shippingCost = 0.0;
        foreach ($shippingCities as $sc) {
            if ((int)$sc['id'] === $cityId) {
                $cityName = current_lang() === 'ar' ? $sc['name_ar'] : $sc['name_en'];
                $shippingCost = (float) $sc['shipping_cost'];
                break;
            }
        }
        $city = $cityName;
        $landmark = trim((string) ($_POST['address_landmark'] ?? ''));
        $notes = trim((string) ($_POST['order_notes'] ?? ''));
        $phone2 = trim((string) ($_POST['customer_phone_2'] ?? ''));
        if ($phone2 !== '') {
            $notes .= ($notes !== '' ? "\n" : "") . (current_lang() === 'ar' ? 'رقم هاتف إضافي: ' : 'Additional phone: ') . $phone2;
        }

        if ($name === '' || $phone === '' || $address === '' || $city === '' || $cityId === 0) {
            $errors[] = t('checkout_err_required') ?? 'Please fill all required fields, including phone.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = t('checkout_err_email');
        }

        $totalForSubmit = 0.0;
        $orderLines = [];
        foreach ($_SESSION['cart'] ?? [] as $lineKey => $qty) {
            $qty = (int) $qty;
            if ($qty < 1) {
                continue;
            }
            $parsed = cart_parse_line_key((string) $lineKey);
            $p = get_cart_line_product($parsed['product_id'], $parsed['variant_id']);
            if ($p !== null) {
                // Stock Check
                $rv = resolve_product_variant($parsed['product_id'], $parsed['variant_id']);
                $vid = $rv['variant_id'] ?? null;
                if ($vid === 0) {
                    $vid = null;
                }

                if ($vid !== null && $pdoMain !== null) {
                    $stockCheck = $pdoMain->prepare('SELECT stock, label_ar, label_en FROM product_variants WHERE id = ?');
                    $stockCheck->execute([$vid]);
                    $vRow = $stockCheck->fetch();
                    if ($vRow && (int)$vRow['stock'] < $qty) {
                        $vLabel = current_lang() === 'ar' ? $vRow['label_ar'] : $vRow['label_en'];
                        $errors[] = "الكمية المطلوبة من ({$p['name']} - {$vLabel}) غير متوفرة. المتاح حالياً: {$vRow['stock']}";
                        continue;
                    }
                }

                $sub = $p['price'] * $qty;
                $totalForSubmit += $sub;

                $orderLines[] = [
                    'product_id' => $parsed['product_id'],
                    'variant_id' => $vid,
                    'name' => $p['name'],
                    'qty' => $qty,
                    'unit_price' => $p['price'],
                    'line_total' => $sub,
                ];
            }
        }

        if ($orderLines === []) {
             $errors[] = t('cart_empty');
        }

        $transferType = $_POST['transfer_type'] ?? 'full';
        $transferAmount = null;
        if ($transferType === 'partial') {
            $transferAmount = (float) ($_POST['transfer_amount'] ?? 0);
        }

        $appliedPromoCode = trim((string) ($_POST['applied_promo_code'] ?? ''));
        $discountAmount = 0.0;
        $promoCodeRow = null;
        if ($appliedPromoCode !== '' && $pdoMain !== null && $errors === []) {
            $promoSt = $pdoMain->prepare('SELECT id, code, discount_percentage, usage_limit, used_count FROM promo_codes WHERE code = ? AND active = 1');
            $promoSt->execute([$appliedPromoCode]);
            $promoCodeRow = $promoSt->fetch();
            if ($promoCodeRow) {
                if ($promoCodeRow['usage_limit'] > 0 && $promoCodeRow['used_count'] >= $promoCodeRow['usage_limit']) {
                    $errors[] = t('checkout_err_promo_limit');
                } else {
                    $discountPct = (int) $promoCodeRow['discount_percentage'];
                    $discountAmount = $totalForSubmit * ($discountPct / 100);
                }
            } else {
                $errors[] = t('checkout_err_promo_invalid');
            }
        }

        if ($errors === []) {
            $pdo = medal_pdo();
            if ($pdo !== null) {
                try {
                    $pdo->beginTransaction();
                    $orderNumber = 'MED-' . strtoupper(bin2hex(random_bytes(4)));
                    $transferImage = null;
                    if (isset($_FILES['transfer_image']) && $_FILES['transfer_image']['error'] === UPLOAD_ERR_OK) {
                        $upDir = __DIR__ . '/assets/uploads/transfers/';
                        if (!is_dir($upDir)) {
                            mkdir($upDir, 0755, true);
                        }
                        $ext = pathinfo($_FILES['transfer_image']['name'], PATHINFO_EXTENSION);
                        $fname = 'transfer_' . uniqid() . '.' . $ext;
                        if (move_uploaded_file($_FILES['transfer_image']['tmp_name'], $upDir . $fname)) {
                            $transferImage = $fname;
                        }
                    }

                    $ins = $pdo->prepare(
                        'INSERT INTO orders (order_number, status, customer_name, customer_email, customer_phone, shipping_address, address_landmark, city, admin_notes, transfer_image, transfer_type, transfer_amount, promo_code, subtotal, discount_amount, shipping_cost, total)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                    );
                    $ins->execute([
                        $orderNumber,
                        'pending',
                        $name,
                        $email !== '' ? $email : null,
                        $phone !== '' ? $phone : null,
                        $address,
                        $landmark !== '' ? $landmark : null,
                        $city,
                        $notes !== '' ? $notes : null,
                        $transferImage,
                        $transferType,
                        $transferAmount,
                        $promoCodeRow ? $promoCodeRow['code'] : null,
                        round($totalForSubmit, 2),
                        $discountAmount > 0 ? round($discountAmount, 2) : null,
                        $shippingCost,
                        round($totalForSubmit - $discountAmount + $shippingCost, 2),
                    ]);
                    $oid = (int) $pdo->lastInsertId();
                    $itemSt = $pdo->prepare(
                        'INSERT INTO order_items (order_id, product_id, variant_id, product_name_snapshot, variant_label_snapshot, qty, unit_price, line_total) VALUES (?,?,?,?,?,?,?,?)'
                    );
                    foreach ($orderLines as $ln) {
                        $vlabel = null;
                        if (!empty($ln['variant_id'])) {
                            $vs = $pdo->prepare('SELECT label_en FROM product_variants WHERE id = ?');
                            $vs->execute([(int) $ln['variant_id']]);
                            $row = $vs->fetch();
                            $vlabel = $row !== false ? (string) $row['label_en'] : null;
                        }
                        $itemSt->execute([
                            $oid,
                            $ln['product_id'],
                            $ln['variant_id'],
                            $ln['name'],
                            $vlabel,
                            $ln['qty'],
                            $ln['unit_price'],
                            $ln['line_total'],
                        ]);
                    }

                    if ($promoCodeRow) {
                        $updPromo = $pdo->prepare('UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?');
                        $updPromo->execute([$promoCodeRow['id']]);
                    }
                    $pdo->commit();

                    // Deduct stock — runs AFTER commit so failure never blocks the order
                    try {
                        $stockSt = $pdo->prepare(
                            'UPDATE product_variants SET stock = GREATEST(0, stock - ?) WHERE id = ?'
                        );
                        foreach ($orderLines as $ln) {
                            if (!empty($ln['variant_id'])) {
                                $stockSt->execute([$ln['qty'], (int) $ln['variant_id']]);
                            }
                        }
                    } catch (Throwable) {
                        // stock column may not exist yet — run migrate.php to add it
                    }
                    
                    // Automatic Client Sync
                    if ($email !== '') {
                        $checkClient = $pdo->prepare('SELECT id FROM clients WHERE email = ?');
                        $checkClient->execute([$email]);
                        $existingClient = $checkClient->fetch();
                        
                        if (!$existingClient) {
                            // Create new client record (no password by default)
                            $insClient = $pdo->prepare('INSERT INTO clients (name, email, phone, created_at) VALUES (?, ?, ?, NOW())');
                            $insClient->execute([$name, $email, $phone]);
                        } else {
                            // Update existing client phone if it was empty
                            $updClient = $pdo->prepare('UPDATE clients SET name = ?, phone = COALESCE(phone, ?) WHERE id = ?');
                            $updClient->execute([$name, $phone, $existingClient['id']]);
                        }
                    }
                    
                    // Send Email Notification
                    if ($email !== '') {
                        try {
                            require_once __DIR__ . '/includes/mail_helper.php';
                            $orderTotal = (float)($totalForSubmit - $discountAmount + $shippingCost);
                            send_order_confirmation_email($email, $orderNumber, $orderTotal, $name);
                        } catch (Throwable $e) {
                            // Email failure shouldn't block the success page
                        }
                    }

                    // Prepare WhatsApp Message for Success Page
                    $orderTotal = (float)($totalForSubmit - $discountAmount + $shippingCost);
                    $waMsg = "طلب جديد من الموقع: " . $orderNumber . "\n";
                    $waMsg .= "العميل: " . $name . "\n";
                    $waMsg .= "الهاتف: " . $phone . "\n";
                    $waMsg .= "المدينة: " . $city . "\n";
                    $waMsg .= "العنوان: " . $address . "\n";
                    $waMsg .= "المنتجات:\n";
                    foreach ($orderLines as $ln) {
                        $waMsg .= "- " . $ln['name'] . " (x" . $ln['qty'] . ") " . format_price($ln['line_total']) . "\n";
                    }
                    $waMsg .= "الإجمالي: " . format_price($orderTotal);
                    $waUrl = "https://wa.me/" . CONTACT_WHATSAPP_E164 . "?text=" . urlencode($waMsg);
                    $_SESSION['last_wa_url'] = $waUrl;

                    $_SESSION['cart'] = [];
                    $done = true;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $errors[] = 'Could not save order: ' . $e->getMessage();
                }
            } else {
                $errors[] = 'Orders require the database. Import schema and run migrate.php.';
            }
        }
    }
}

$lines = [];
$total = 0.0;
foreach ($_SESSION['cart'] ?? [] as $lineKey => $qty) {
    $qty = (int) $qty;
    if ($qty < 1) {
        continue;
    }
    $parsed = cart_parse_line_key((string) $lineKey);
    $p = get_cart_line_product($parsed['product_id'], $parsed['variant_id']);
    if ($p !== null) {
        $sub = $p['price'] * $qty;
        $total += $sub;

        $rv = resolve_product_variant($parsed['product_id'], $parsed['variant_id']);
        $vid = $rv['variant_id'] ?? null;
        if ($vid === 0) {
            $vid = null;
        }

        $lines[] = [
            'product_id' => $parsed['product_id'],
            'variant_id' => $vid,
            'name' => $p['name'],
            'image' => $p['image'],
            'qty' => $qty,
            'unit_price' => $p['price'],
            'line_total' => $sub,
            'line_key' => (string) $lineKey,
        ];
    }
}

require __DIR__ . '/includes/header.php';
?>

<style>
/* Neo Checkout Overrides */
:root {
  --neo-bg: #fdfdfd;
  --neo-border: #e2e8f0;
  --neo-dashed: #94a3b8;
  --neo-text: #334155;
  --neo-heading: #0f172a;
  --neo-muted: #64748b;
  --neo-input-bg: #f8fafc;
  --neo-radius: 12px;
  --neo-font: "Inter", "Tajawal", system-ui, sans-serif;
}
.theme-gulf .checkout-layout { background: var(--neo-bg); color: var(--neo-text); font-family: var(--neo-font); padding: 3rem 0; text-align: start; }
.theme-gulf .checkout-layout h1, .theme-gulf .checkout-layout h2, .theme-gulf .checkout-layout h3 { color: var(--neo-heading); font-family: var(--neo-font); }
.neo-container { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem; display: flex; flex-direction: column; gap: 3rem; }
@media(min-width: 900px) { .neo-container { flex-direction: row; align-items: flex-start; } }
.neo-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1.25rem; }
.neo-sidebar { width: 100%; max-width: 420px; }
.neo-box { background: #fff; border: 1px solid var(--neo-border); border-radius: var(--neo-radius); padding: 1.25rem; }
.neo-dashed-box { border: 2px dashed #cbd5e1; border-radius: 16px; padding: 2rem; background: #fff; }
.neo-progress-text { font-weight: 600; font-size: 0.95rem; margin-bottom: 0.75rem; text-align: center; }
.neo-progress-bar { background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden; }
.neo-progress-fill { background: #1e293b; width: 40%; height: 100%; border-radius: 4px; }
.neo-upsell { display: flex; align-items: center; gap: 1rem; }
.neo-upsell-img { width: 48px; height: 48px; background: #f1f5f9; border-radius: 6px; flex-shrink: 0; background-size: cover; background-position: center; }
.neo-upsell-info { flex: 1; }
.neo-upsell-title { font-size: 0.95rem; font-weight: 600; color: var(--neo-text); margin-bottom: 0.15rem; }
.neo-upsell-price { font-size: 0.9rem; font-weight: 700; color: var(--neo-heading); }
.neo-btn-var { border: 1px solid var(--neo-border); background: #fff; font-size: 0.8rem; padding: 0.4rem 0.6rem; border-radius: 6px; cursor: pointer; font-weight: 600; color: var(--neo-text); display: flex; align-items: center; gap: 0.4rem; white-space: nowrap; }
.neo-nav-btn { border: none; background: none; color: var(--neo-muted); cursor: pointer; padding: 0.2rem; }
.neo-form-title { font-size: 1.15rem; margin-top: 0; margin-bottom: 1.75rem; font-weight: 500; color: var(--neo-heading); }
.neo-input-wrap { position: relative; margin-bottom: 1rem; }
.neo-input-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 1.15rem; height: 1.15rem; color: #94a3b8; pointer-events: none; }
.neo-input-wrap input, .neo-input-wrap textarea { width: 100%; background: var(--neo-input-bg); border: 1px solid transparent; padding: 1.1rem 1rem 1.1rem 2.8rem; border-radius: 8px; font-size: 0.95rem; color: var(--neo-text); font-family: inherit; transition: border-color 0.2s; }
.neo-input-wrap textarea { min-height: 80px; resize: vertical; }
.neo-input-wrap input:focus, .neo-input-wrap textarea:focus { outline: none; border-color: #cbd5e1; background: #fff; }
.neo-input-wrap input::placeholder, .neo-input-wrap textarea::placeholder { color: #94a3b8; }
.neo-select-wrap { position: relative; margin-bottom: 1rem; }
.neo-select-wrap select { appearance: none; width: 100%; background: var(--neo-input-bg); border: 1px solid transparent; padding: 1.1rem 2.8rem 1.1rem 2.8rem; border-radius: 8px; font-size: 0.95rem; color: var(--neo-text); font-family: inherit; }
.neo-select-arrow { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; color: #94a3b8; pointer-events: none; }
.neo-product { display: flex; gap: 1.25rem; margin-bottom: 1.5rem; }
.neo-product-img { width: 80px; height: 80px; background-color: #f8fafc; border-radius: 8px; flex-shrink: 0; background-size: cover; background-position: center; border: 1px solid var(--neo-border); display: block; }
.neo-product-info { flex: 1; }
.neo-product-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.2rem; }
.neo-product-title { font-size: 0.95rem; font-weight: 600; color: var(--neo-heading); line-height: 1.3; }
.neo-product-price { font-size: 0.95rem; font-weight: 600; color: var(--neo-heading); white-space: nowrap; margin-left: 0.5rem; }
.neo-product-meta { font-size: 0.85rem; color: var(--neo-muted); margin-bottom: 0.15rem; }
.neo-qty-controls { display: flex; align-items: center; gap: 1rem; margin-top: 0.75rem; }
.neo-qty-box { display: flex; border: 1px solid var(--neo-border); border-radius: 6px; overflow: hidden; background: #fff; }
.neo-qty-btn { padding: 0.25rem 0.6rem; background: #fff; border: none; cursor: pointer; color: var(--neo-text); font-size: 1rem; line-height: 1; }
.neo-qty-input { width: 2.5rem; text-align: center; border: none; border-left: 1px solid var(--neo-border); border-right: 1px solid var(--neo-border); font-size: 0.9rem; color: var(--neo-heading); -moz-appearance: textfield; }
.neo-trash-btn { color: #ef4444; background: none; border: none; cursor: pointer; padding: 0.25rem; display: flex; align-items: center; justify-content: center; }
.neo-summary-table { border-top: 1px solid var(--neo-border); padding-top: 1.5rem; margin-top: 1rem; }
.neo-summary-row { display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.95rem; color: var(--neo-muted); }
.neo-summary-row.neo-total { font-size: 1.1rem; font-weight: 700; color: var(--neo-heading); margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--neo-border); }
.neo-summary-val { font-weight: 600; color: var(--neo-heading); }
.neo-submit { width: 100%; background: var(--neo-heading); color: #fff; border: none; padding: 1.15rem; border-radius: 8px; font-size: 1.05rem; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 1.5rem; }
.neo-submit:hover { background: #000; }

[dir="rtl"] .neo-input-icon { left: auto; right: 1rem; }
[dir="rtl"] .neo-input-wrap input, [dir="rtl"] .neo-input-wrap textarea { padding: 1.1rem 2.8rem 1.1rem 1rem; }
[dir="rtl"] .neo-select-wrap select { padding: 1.1rem 2.8rem 1.1rem 2.8rem; }
[dir="rtl"] .neo-select-arrow { right: auto; left: 1rem; }
[dir="rtl"] .neo-product-price { margin-left: 0; margin-right: 0.5rem; }
[dir="rtl"] .neo-nav-btn svg { transform: scaleX(-1); }
</style>

<section class="checkout-layout">
    <div class="neo-container">
        <?php if ($done): ?>
            <div class="neo-main" style="align-items: center; text-align: center; max-width: 600px; margin: 0 auto;">
                <div class="neo-box" style="width: 100%; padding: 4rem 2rem;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem;"><?= esc(t('checkout_success_title')) ?></h2>
                    <p style="color: var(--neo-muted); margin-bottom: 2rem;"><?= esc(t('checkout_success_lead', ['number' => $orderNumber])) ?></p>
                    
                    <?php if (isset($_SESSION['last_wa_url'])): ?>
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
                            <p style="color: #166534; font-weight: 600; margin-bottom: 1rem;">
                                <?= current_lang() === 'ar' ? 'يرجى الضغط على الزر أدناه لتأكيد طلبك عبر واتساب' : 'Please click the button below to confirm your order via WhatsApp' ?>
                            </p>
                            <a href="<?= esc($_SESSION['last_wa_url']) ?>" target="_blank" style="background: #25d366; color: white; padding: 1rem 2rem; border-radius: 50px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 700; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                <?= current_lang() === 'ar' ? 'تأكيد الطلب عبر واتساب' : 'Confirm on WhatsApp' ?>
                            </a>
                        </div>
                        <?php unset($_SESSION['last_wa_url']); ?>
                    <?php endif; ?>
                    
                    <a class="neo-submit" href="<?= esc(url('index.php')) ?>" style="display:inline-block; width:auto; padding: 0.8rem 2rem; text-decoration:none;"><?= esc(t('nav_home')) ?></a>
                </div>
            </div>
        <?php elseif ($lines === []): ?>
            <div class="neo-main" style="align-items: center; text-align: center; max-width: 600px; margin: 0 auto;">
                <div class="neo-box" style="width: 100%; padding: 4rem 2rem;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem;"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    <p style="font-size: 1.1rem; color: var(--neo-muted); margin-bottom: 2rem;"><?= esc(t('cart_empty')) ?></p>
                    <a href="<?= esc(url('products.php')) ?>" class="neo-submit" style="display:inline-block; width:auto; padding: 0.8rem 2rem; text-decoration:none;"><?= esc(t('cart_browse')) ?></a>
                </div>
            </div>
        <?php else: ?>
            
            <div class="neo-main">

                <?php if ($errors !== []): ?>
                    <div style="background: #fef2f2; border: 1px solid #f87171; color: #b91c1c; padding: 1rem; border-radius: 8px;">
                        <ul style="margin: 0; padding-left: 1.5rem;">
                            <?php foreach ($errors as $e): ?>
                                <li><?= esc($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Form Container -->
                <div class="neo-dashed-box">
                    <h3 class="neo-form-title"><?= esc(current_lang() == 'ar' ? 'يرجى تعبئة بياناتك لإتمام الطلب' : 'Please fill your information to complete the order') ?></h3>
                    
                    <form method="post" action="<?= esc(url('checkout.php')) ?>" id="main-checkout-form" enctype="multipart/form-data">
                        <input type="hidden" name="lang" value="<?= esc(current_lang()) ?>">
                        <input type="hidden" name="action" value="submit_order">
                        <input type="hidden" name="applied_promo_code" id="applied_promo_code" value="">
                        <input type="hidden" name="address_landmark" id="address_landmark_hidden" value="">

                        <!-- Name -->
                        <div class="neo-input-wrap">
                            <svg class="neo-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <input type="text" name="customer_name" placeholder="<?= esc(t('checkout_name')) ?>" required value="<?= esc(trim((string) ($_POST['customer_name'] ?? ''))) ?>">
                        </div>

                        <!-- Phone -->
                        <div class="neo-input-wrap">
                            <svg class="neo-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            <input type="tel" name="customer_phone" placeholder="<?= esc(t('checkout_phone')) ?>" required value="<?= esc(trim((string) ($_POST['customer_phone'] ?? ''))) ?>" dir="ltr">
                        </div>

                        <!-- Phone 2 -->
                        <div class="neo-input-wrap">
                            <svg class="neo-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            <input type="tel" name="customer_phone_2" placeholder="<?= esc(t('checkout_phone_2')) ?>" value="<?= esc(trim((string) ($_POST['customer_phone_2'] ?? ''))) ?>" dir="ltr">
                        </div>

                        <!-- Email (Optional, hidden in design but kept functionality) -->
                        <div class="neo-input-wrap">
                            <svg class="neo-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            <input type="email" name="customer_email" placeholder="<?= esc(t('checkout_email')) ?> (<?= esc(current_lang() == 'ar' ? 'إختياري' : 'Optional') ?>)" value="<?= esc(trim((string) ($_POST['customer_email'] ?? ''))) ?>">
                        </div>

                        <!-- City Selection -->
                        <div class="neo-select-wrap">
                            <svg class="neo-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16"></path><path d="M1 21h22"></path><path d="M8 7h2"></path><path d="M8 11h2"></path><path d="M8 15h2"></path><path d="M14 7h2"></path><path d="M14 11h2"></path><path d="M14 15h2"></path></svg>
                            <select name="city_id" id="checkout_city_id" required>
                                <option value="" disabled><?= esc(t('checkout_city')) ?></option>
                                <?php foreach ($shippingCities as $sc): ?>
                                    <?php $isCairo = (stripos((string)$sc['name_en'], 'Cairo') !== false || mb_strpos((string)$sc['name_ar'], 'قاهر') !== false); ?>
                                    <option value="<?= (int) $sc['id'] ?>" data-cost="<?= (float) $sc['shipping_cost'] ?>" <?= $isCairo ? 'selected' : '' ?>><?= esc(current_lang() === 'ar' ? $sc['name_ar'] : $sc['name_en']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <svg class="neo-select-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>

                        <!-- Address Section -->
                        <div class="neo-input-wrap">
                            <svg class="neo-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--gold-dark);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <input type="text" name="shipping_address" placeholder="<?= esc(t('checkout_address')) ?>" required value="<?= esc(trim((string) ($_POST['shipping_address'] ?? ''))) ?>">
                        </div>

                        <!-- Transfer Type -->
                        <div class="neo-box" style="margin-bottom: 1rem; border-color: var(--gold-dark); background: rgba(212, 175, 55, 0.05);">
                            <label style="display:block; margin-bottom:0.75rem; font-weight:600; color:var(--neo-heading);">
                                <?= esc(current_lang() == 'ar' ? 'ماذا ستحول الآن؟' : 'What will you transfer now?') ?>
                            </label>
                            <div style="display:flex; flex-direction:column; gap:0.5rem;">
                                <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                                    <input type="radio" name="transfer_type" value="shipping" onchange="togglePartialInput(false)">
                                    <?= esc(current_lang() == 'ar' ? 'سأحول مصاريف الشحن فقط' : 'I will transfer shipping cost only') ?>
                                </label>
                                <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                                    <input type="radio" name="transfer_type" value="full" checked onchange="togglePartialInput(false)">
                                    <?= esc(current_lang() == 'ar' ? 'سأحول ثمن الأوردر كاملاً' : 'I will transfer full order price') ?>
                                </label>
                                <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                                    <input type="radio" name="transfer_type" value="partial" id="partial_radio" onchange="togglePartialInput(true)">
                                    <?= esc(current_lang() == 'ar' ? 'سأحول جزء من ثمن الأوردر' : 'I will transfer part of order price') ?>
                                </label>
                                <div id="partial_amount_container" style="display:none; margin-top:0.5rem; padding-inline-start:1.5rem;">
                                    <input type="number" name="transfer_amount" placeholder="<?= esc(current_lang() == 'ar' ? 'أدخل المبلغ' : 'Enter amount') ?>" style="width:120px; padding:0.5rem; border:1px solid var(--border); border-radius:4px;">
                                </div>
                            </div>
                        </div>

                        <!-- Transfer Image (Optional) -->
                        <div class="neo-input-wrap">
                            <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--neo-muted);">
                                <?= esc(current_lang() == 'ar' ? 'صورة التحويل (اختياري)' : 'Transfer Proof Image (Optional)') ?>
                            </label>
                            <input type="file" name="transfer_image" accept="image/*" style="padding: 0.8rem 1rem;">
                        </div>

                    </form>

                    <script>
                    function togglePartialInput(show) {
                        document.getElementById('partial_amount_container').style.display = show ? 'block' : 'none';
                    }
                    </script>
                </div>
            </div>
            
            <div class="neo-sidebar">
                <div class="neo-box" style="padding: 1.5rem;">
                    
                    <div style="max-height: 50vh; overflow-y: auto; padding-right: 0.5rem; margin-bottom: 1rem;">
                        <?php foreach($lines as $ln): ?>
                        <div class="neo-product">
                            <a href="<?= esc(url('product.php?id=' . $ln['product_id'])) ?>" class="neo-product-img product-visual <?= esc(product_image_class($ln['image'])) ?>"<?= product_image_style($ln['image']) ?>></a>
                            
                            <div class="neo-product-info">
                                <div class="neo-product-head">
                                    <h4 class="neo-product-title"><a href="<?= esc(url('product.php?id=' . $ln['product_id'])) ?>" style="text-decoration:none; color:inherit;"><?= esc($ln['name']) ?></a></h4>
                                    <div class="neo-product-price" dir="ltr" data-line-price-key="<?= esc($ln['line_key']) ?>"><?= esc(format_price($ln['line_total'])) ?></div>
                                </div>
                                <!-- Variant info removed for now as label is not fetched -->
                                
                                <!-- Redundant line total removed -->
                                
                                <div class="neo-qty-controls" data-line-key="<?= esc($ln['line_key']) ?>">
                                    <div class="neo-qty-box">
                                        <button type="button" class="neo-qty-btn btn-qty-plus">+</button>
                                        <input type="number" name="qty" value="<?= esc((string)$ln['qty']) ?>" min="1" class="neo-qty-input" readonly>
                                        <button type="button" class="neo-qty-btn btn-qty-minus">-</button>
                                    </div>
                                    
                                    <button type="button" class="neo-trash-btn btn-remove-item">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div id="promo-toggle-wrapper" style="margin-bottom: 1rem;">
                        <button type="button" id="toggle-promo-btn" style="background:none; border:none; color:var(--gold-dark); font-weight:600; font-size:1.1rem; cursor:pointer; padding:0; font-family:inherit; text-decoration:none;">
                            <?= esc(current_lang() == 'ar' ? 'هل لديك كود خصم؟' : 'Have a discount code?') ?>
                        </button>
                    </div>

                    <div id="promo-input-section" style="display: none; margin-bottom: 1.5rem; gap: 0.5rem; transition: all 0.3s ease;">
                        <input type="text" id="promo-code-input" placeholder="<?= esc(t('checkout_promo_code_label')) ?>" style="flex:1; padding:0.8rem 1rem; border:1px solid var(--neo-border); border-radius:8px; font-family:inherit; text-transform:uppercase;">
                        <button type="button" id="btn-apply-promo" style="background:var(--neo-heading); color:#fff; border:none; border-radius:8px; padding:0 1.5rem; cursor:pointer; font-weight:600; font-size: 0.95rem;"><?= esc(t('checkout_apply')) ?></button>
                    </div>
                    <div id="promo-message" style="margin-bottom: 1.5rem; font-size:0.9rem;"></div>
                    
                    <div class="neo-summary-table">
                        <div class="neo-summary-row">
                            <span><?= esc(t('checkout_product_total')) ?></span>
                            <span class="neo-summary-val" dir="ltr" id="ui-subtotal" data-val="<?= esc((string)$total) ?>"><?= esc(format_price($total)) ?></span>
                        </div>
                        <div class="neo-summary-row" id="row-discount" style="display:none; color: #10b981;">
                            <span><?= esc(t('checkout_discount')) ?></span>
                            <span class="neo-summary-val" dir="ltr" id="ui-discount"><strong>—</strong></span>
                        </div>
                        <div class="neo-summary-row">
                            <span><?= esc(t('checkout_shipping_cost')) ?></span>
                            <span class="neo-summary-val" dir="ltr" id="ui-shipping"><strong>—</strong></span>
                        </div>
                        <div class="neo-summary-row neo-total">
                            <span><?= esc(t('checkout_total')) ?></span>
                            <strong dir="ltr" id="ui-total"><?= esc(format_price($total)) ?></strong>
                        </div>
                    </div>
                    
                </div>
                
                <button type="button" onclick="document.getElementById('main-checkout-form').submit()" class="neo-submit"><?= esc(t('checkout_complete_order')) ?></button>
            </div>
            
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var citySelect = document.getElementById('checkout_city_id');
    var uiShipping = document.getElementById('ui-shipping');
    var uiTotal = document.getElementById('ui-total');
    var discountRow = document.getElementById('row-discount');
    var uiDiscount = document.getElementById('ui-discount');
    var subtotal = parseFloat(document.getElementById('ui-subtotal').getAttribute('data-val')) || 0;
    var discountPercentage = 0;
    var currency = <?= json_encode(t('currency')) ?>;

    function updateTotals() {
        var cost = 0;
        if (citySelect && citySelect.selectedIndex >= 0) {
            var opt = citySelect.options[citySelect.selectedIndex];
            cost = parseFloat(opt.getAttribute('data-cost')) || 0;
            if (!isNaN(cost) && cost > 0) {
                uiShipping.innerHTML = '<strong>' + cost.toFixed(2) + ' ' + currency + '</strong>';
            } else {
                uiShipping.innerHTML = '<strong>—</strong>';
            }
        } else {
            uiShipping.innerHTML = '<strong>—</strong>';
        }

        var discountAmt = subtotal * (discountPercentage / 100);
        if (discountAmt > 0) {
            discountRow.style.display = 'flex';
            uiDiscount.innerHTML = '<strong>-' + discountAmt.toFixed(2) + ' ' + currency + '</strong>';
        } else {
            discountRow.style.display = 'none';
        }

        uiTotal.innerHTML = (subtotal - discountAmt + cost).toFixed(2) + ' ' + currency;
    }

    function updateCartAjax(lineKey, action, newQty) {
        var fd = new FormData();
        fd.append('line_key', lineKey);
        fd.append('action', action);
        if (newQty !== undefined) fd.append('qty', newQty);
        if (hiddenPromoField && hiddenPromoField.value) fd.append('promo_code', hiddenPromoField.value);

        fetch(<?= json_encode(url('ajax_update_cart.php')) ?>, {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (data.new_qty > 0) {
                    var row = document.querySelector('.neo-qty-controls[data-line-key="' + lineKey + '"]');
                    if (row) {
                        row.querySelector('.neo-qty-input').value = data.new_qty;
                    }
                    var priceEl = document.querySelector('[data-line-price-key="' + lineKey + '"]');
                    if (priceEl) {
                        priceEl.innerText = data.line_total;
                    }
                } else {
                    var btn = document.querySelector('.neo-qty-controls[data-line-key="' + lineKey + '"]');
                    if (btn) {
                        var itemRow = btn.closest('.neo-product');
                        if (itemRow) itemRow.remove();
                    }
                    if (data.cart_count === 0) location.reload();
                }

                subtotal = data.subtotal;
                var subEl = document.getElementById('ui-subtotal');
                if (subEl) subEl.innerText = data.subtotal_formatted;
                updateTotals();

                var badge = document.querySelector('.cart-badge');
                if (badge) {
                    if (data.cart_count > 0) {
                        badge.innerText = data.cart_count;
                    } else {
                        badge.remove();
                    }
                }
            }
        });
    }

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-qty-plus, .btn-qty-minus, .btn-remove-item');
        if (!btn) return;

        var container = btn.closest('.neo-qty-controls');
        var lineKey = container.getAttribute('data-line-key');
        var input = container.querySelector('.neo-qty-input');
        var currentQty = parseInt(input ? input.value : "0");

        if (btn.classList.contains('btn-qty-plus')) {
            updateCartAjax(lineKey, 'update_qty', currentQty + 1);
        } else if (btn.classList.contains('btn-qty-minus')) {
            if (currentQty > 1) {
                updateCartAjax(lineKey, 'update_qty', currentQty - 1);
            } else {
                updateCartAjax(lineKey, 'remove');
            }
        } else if (btn.classList.contains('btn-remove-item')) {
            updateCartAjax(lineKey, 'remove');
        }
    });

    var btnApplyPromo = document.getElementById('btn-apply-promo');
    var promoInput = document.getElementById('promo-code-input');
    var promoMessage = document.getElementById('promo-message');
    var hiddenPromoField = document.getElementById('applied_promo_code');
    var togglePromoBtn = document.getElementById('toggle-promo-btn');
    var promoSection = document.getElementById('promo-input-section');

    if (togglePromoBtn && promoSection) {
        togglePromoBtn.addEventListener('click', function() {
            promoSection.style.display = 'flex';
            togglePromoBtn.parentElement.style.display = 'none';
        });
    }

    if (btnApplyPromo && promoInput) {
        btnApplyPromo.addEventListener('click', function() {
            var code = promoInput.value.trim();
            if (code === '') return;
            
            btnApplyPromo.disabled = true;
            btnApplyPromo.innerText = '...';
            
            var fd = new FormData();
            fd.append('code', code);
            
            fetch(<?= json_encode(url('ajax_apply_promo.php')) ?>, {
                method: 'POST',
                body: fd
            }).then(r => r.json()).then(data => {
                btnApplyPromo.disabled = false;
                btnApplyPromo.innerText = <?= json_encode(t('checkout_apply')) ?>;
                
                if (data.error) {
                    promoMessage.style.color = '#ef4444';
                    promoMessage.innerText = data.error;
                    discountPercentage = 0;
                    hiddenPromoField.value = '';
                } else if (data.success) {
                    promoMessage.style.color = '#10b981';
                    promoMessage.innerText = <?= json_encode(t('checkout_promo_applied')) ?> + ' ' + data.discount_percentage + '%';
                    discountPercentage = data.discount_percentage;
                    hiddenPromoField.value = code;
                }
                updateTotals();
            }).catch(e => {
                btnApplyPromo.disabled = false;
                btnApplyPromo.innerText = <?= json_encode(t('checkout_apply')) ?>;
                promoMessage.style.color = '#ef4444';
                promoMessage.innerText = 'Error applying promo code.';
            });
        });

        promoInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnApplyPromo.click();
            }
        });
    }

    if (citySelect) {
        citySelect.addEventListener('change', updateTotals);
    }
    updateTotals();
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
