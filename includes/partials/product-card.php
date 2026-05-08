<?php
declare(strict_types=1);
/** @var array $p product row (localized) */
/** @var bool $showBestseller */
$showBestseller = $showBestseller ?? false;
?>
<article class="product-card">
    <div class="product-card__inner">
        <a href="<?= esc(url('product.php?id=' . $p['id'])) ?>" class="product-card__link" aria-label="<?= esc($p['name']) ?>">
            <div class="product-card__media">
                <?php if ($showBestseller && !empty($p['bestseller'])): ?>
                    <div class="product-card__badge"><?= esc(t('badge_bestseller')) ?></div>
                <?php endif; ?>
                <?php if (!empty($p['is_offer'])): ?>
                    <div class="product-card__badge product-card__badge--offer"><?= esc(t('admin_flag_offer')) ?></div>
                <?php endif; ?>
                <div class="product-card__image-wrap">
                    <div class="<?= esc(product_image_class($p['image'], 'product-card__image')) ?>" role="img" aria-label="<?= esc($p['name']) ?>"<?= product_image_style($p['image']) ?>></div>
                </div>
                <div class="product-card__overlay">
                    <span class="product-card__view-text"><?= esc(t('view_product') ?? 'View Details') ?></span>
                </div>
            </div>
            <div class="product-card__content">
                <h3 class="product-card__title"><?= esc($p['name']) ?></h3>
                <div class="product-card__notes"><?= esc($p['notes']) ?></div>
                <div class="product-card__price-row">
                    <span class="product-card__price"><?= esc(format_price($p['price'])) ?></span>
                </div>
            </div>
        </a>
        <div class="product-card__footer">
            <button type="button" class="product-card__btn-quick btn-quick-configure" 
                    data-product="<?= base64_encode(json_encode($p + ['variants' => get_product_variants($p['id'])])) ?>"
                    aria-label="<?= esc(t('btn_configure')) ?> - <?= esc($p['name']) ?>">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                <span><?= esc(t('btn_configure')) ?></span>
            </button>
        </div>
    </div>
</article>
