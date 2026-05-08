<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/products.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$product = $id > 0 ? get_product_by_id_localized($id) : null;

if ($product !== null) {
    try {
        $pdo = medal_pdo();
        if ($pdo) {
            $pdo->exec("UPDATE products SET view_count = view_count + 1 WHERE id = $id");
        }
    } catch (Throwable $e) {}
}

if ($product === null) {
    http_response_code(404);
    $pageTitle = t('product_not_found_title');
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="section">
        <div class="container narrow">
            <h1><?= esc(t('product_not_found_title')) ?></h1>
            <p><?= esc(t('product_not_found_text')) ?> <a href="<?= esc(url('products.php')) ?>"><?= esc(t('product_not_found_link')) ?></a>.</p>
        </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $product['name'];
$variants = get_product_variants($id);
$selectedVariantId = null;
if ($variants !== []) {
    $req = isset($_GET['v']) ? (int) $_GET['v'] : 0;
    foreach ($variants as $v) {
        if ($req > 0 && $v['id'] === $req) {
            $selectedVariantId = $v['id'];
            break;
        }
    }
    if ($selectedVariantId === null) {
        $selectedVariantId = $variants[0]['id'];
    }
    $rv = resolve_product_variant($id, $selectedVariantId);
    if ($rv !== null) {
        $product['price'] = $rv['price'];
    }
}

$related = array_values(array_filter(
    get_products_localized(),
    static fn ($p) => $p['id'] !== $product['id'] && $p['category'] === $product['category']
));
$related = array_slice($related, 0, 2);
if ($related === []) {
    $related = array_slice(array_filter(get_products_localized(), static fn ($p) => $p['id'] !== $product['id']), 0, 2);
}

require __DIR__ . '/includes/header.php';
?>

<section class="product-detail">
    <div class="container product-detail-grid">
        <div class="product-detail-visual-wrap">
            <div class="<?= esc(product_image_class($product['image'], 'product-detail-visual product-visual')) ?> product-visual--hero" role="img" aria-label="<?= esc($product['name']) ?>"<?= product_image_style($product['image']) ?>></div>
        </div>
        <div class="product-detail-copy">
            <p class="product-cat"><?= esc(category_label($product['category'])) ?></p>
            <h1><?= esc($product['name']) ?></h1>
            <p class="product-notes product-notes--large"><?= esc($product['notes']) ?></p>
            <p class="product-detail-desc"><?= esc($product['description']) ?></p>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem">
                <p class="product-price product-price--large" id="product-price-display" style="margin-bottom:0"><?= esc(format_price($product['price'])) ?></p>
                <div id="stock-display" style="font-size:0.9rem; font-weight:600">
                    <?php 
                    $initialStock = isset($variants[0]['stock']) ? (int)$variants[0]['stock'] : 0;
                    if ($initialStock > 0): ?>
                        <span class="admin-muted">الكمية المتوفرة: </span><span id="available-stock" style="color: #27ae60;"><?= $initialStock ?></span>
                    <?php else: ?>
                        <span id="available-stock" style="color: #e74c3c;">نفذت الكمية</span>
                    <?php endif; ?>
                </div>
            </div>
            <form class="add-form" method="post" action="<?= esc(url('cart.php')) ?>" id="add-to-cart-form">
                <input type="hidden" name="lang" value="<?= esc(current_lang()) ?>">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <?php if ($variants !== []): ?>
                    <p class="qty-label" id="variant-label"><?= esc(t('variant_label')) ?></p>
                    <div class="variant-pills" role="radiogroup" aria-labelledby="variant-label">
                        <?php foreach ($variants as $v): ?>
                            <?php
                            $optLabel = current_lang() === 'ar' ? $v['label_ar'] : $v['label_en'];
                            $isSel = $selectedVariantId !== null && $v['id'] === $selectedVariantId;
                            $isOutOfStock = (int)$v['stock'] <= 0;
                            ?>
                            <label class="variant-pill<?= $isSel ? ' is-active' : '' ?><?= $isOutOfStock ? ' is-out-of-stock' : '' ?>" style="<?= $isOutOfStock ? 'opacity: 0.5; cursor: not-allowed;' : '' ?>">
                                <input
                                    type="radio"
                                    name="variant_id"
                                    value="<?= (int) $v['id'] ?>"
                                    class="variant-pill__input"
                                    data-price="<?= esc((string) $v['price']) ?>"
                                    data-stock="<?= (int) $v['stock'] ?>"
                                    <?= $isSel ? 'checked' : '' ?>
                                    <?= $isOutOfStock ? 'disabled' : '' ?>
                                >
                                <span class="variant-pill__body">
                                    <span class="variant-pill__label"><?= esc($optLabel) ?></span>
                                    <span class="variant-pill__price"><?= esc(format_price($v['price'])) ?></span>
                                    <?php if ($isOutOfStock): ?>
                                        <span style="font-size: 0.7rem; color: #e74c3c; font-weight: bold;">(نفذت)</span>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <label class="qty-label" for="qty"><?= esc(t('qty')) ?></label>
                <div class="add-form-row">
                    <input type="number" id="qty" name="qty" value="1" min="1" max="<?= $initialStock > 0 ? $initialStock : 1 ?>" class="qty-input" dir="ltr" <?= $initialStock <= 0 ? 'disabled' : '' ?>>
                    <button type="submit" id="add-to-cart-btn" class="btn btn-primary" <?= $initialStock <= 0 ? 'disabled style="background: #ccc; box-shadow: none;"' : '' ?>>
                        <?= $initialStock > 0 ? esc(t('add_to_cart')) : 'نفذت الكمية' ?>
                    </button>
                </div>
            </form>
            <script>
            (function () {
                var priceEl = document.getElementById('product-price-display');
                var stockEl = document.getElementById('available-stock');
                var qtyInput = document.getElementById('qty');
                var addBtn = document.getElementById('add-to-cart-btn');
                var inputs = document.querySelectorAll('.variant-pill__input');
                if (!priceEl || !inputs.length) return;
                var currency = <?= json_encode(t('currency')) ?>;
                function fmt(n) {
                    return Number(n).toFixed(2) + ' ' + currency;
                }
                function syncActive() {
                    inputs.forEach(function (inp) {
                        var lab = inp.closest('.variant-pill');
                        if (lab) lab.classList.toggle('is-active', inp.checked);
                        if (inp.checked) {
                            var s = parseInt(inp.getAttribute('data-stock')) || 0;
                            if (stockEl) {
                                if (s > 0) {
                                    stockEl.parentElement.innerHTML = '<span class="admin-muted">الكمية المتوفرة: </span><span id="available-stock" style="color: #27ae60;">' + s + '</span>';
                                    // Refresh stockEl reference after innerHTML change
                                    stockEl = document.getElementById('available-stock');
                                    if (qtyInput) {
                                        qtyInput.disabled = false;
                                        qtyInput.max = s;
                                        if (parseInt(qtyInput.value) > s) qtyInput.value = s;
                                    }
                                    if (addBtn) {
                                        addBtn.disabled = false;
                                        addBtn.style.background = '';
                                        addBtn.style.boxShadow = '';
                                        addBtn.textContent = <?= json_encode(t('add_to_cart')) ?>;
                                    }
                                } else {
                                    stockEl.parentElement.innerHTML = '<span id="available-stock" style="color: #e74c3c;">نفذت الكمية</span>';
                                    stockEl = document.getElementById('available-stock');
                                    if (qtyInput) qtyInput.disabled = true;
                                    if (addBtn) {
                                        addBtn.disabled = true;
                                        addBtn.style.background = '#ccc';
                                        addBtn.style.boxShadow = 'none';
                                        addBtn.textContent = 'نفذت الكمية';
                                    }
                                }
                            }
                        }
                    });
                }
                inputs.forEach(function (inp) {
                    inp.addEventListener('change', function () {
                        syncActive();
                        var p = inp.getAttribute('data-price');
                        if (p) priceEl.textContent = fmt(p);
                        var url = new URL(window.location.href);
                        url.searchParams.set('v', inp.value);
                        window.history.replaceState({}, '', url);
                    });
                });
                syncActive();
            })();
            </script>
        </div>
    </div>
</section>

<?php if ($related !== []): ?>
<section class="section related">
    <div class="container">
        <h2><?= esc(t('related_title')) ?></h2>
        <div class="product-grid">
            <?php foreach ($related as $p): ?>
                <?php $showBestseller = false; require __DIR__ . '/includes/partials/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
