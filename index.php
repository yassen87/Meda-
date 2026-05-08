<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/products.php';

$pageTitle = t('page_home');

$cAll = count_products_in_category('all');
$cWomen = count_products_in_category('women');
$cMen = count_products_in_category('men');
$cUnisex = count_products_in_category('unisex');
$cKhinat = count_products_in_category('khinat');

$bestsellers = get_bestsellers_localized(4);
$winterPicks = get_seasonal_localized('winter', 4);
$summerPicks = get_seasonal_localized('summer', 4);

require __DIR__ . '/includes/header.php';
?>

<section class="hero" id="hero-slider-section">
    <div class="hero-slides">
        <!-- Slide 0: Logo -->
        <div class="hero-slide is-active" data-index="0" style="background-image: url('<?= esc(url('assets/img/logo.png')) ?>');"></div>
        
        <?php
        $pdo = medal_pdo();
        $heroOffers = [];
        if ($pdo) {
            try {
                $st = $pdo->query("SELECT image_key FROM homepage_offers ORDER BY sort_order ASC LIMIT 8");
                $heroOffers = $st->fetchAll();
            } catch (Throwable $e) {}
        }
        foreach ($heroOffers as $idx => $offer): ?>
            <div class="hero-slide" data-index="<?= $idx + 1 ?>" style="background-image: url('<?= esc(url('assets/uploads/' . $offer['image_key'])) ?>');"></div>
        <?php endforeach; ?>
    </div>

    <div class="container hero-content">
        <p class="hero-eyebrow"><?= esc(t('hero_est')) ?></p>
        <h1 class="hero-title hero-title--mega"><?= esc(t('hero_main_line')) ?></h1>
        <p class="hero-subline"><?= esc(t('hero_sub_line')) ?></p>
        <p class="hero-lead"><?= esc(t('hero_lead_af')) ?></p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="<?= esc(url('products.php')) ?>"><?= esc(t('hero_cta_shop')) ?></a>
            <a class="btn btn-outline" href="<?= esc(contact_whatsapp_url()) ?>" target="_blank" rel="noopener noreferrer"><?= esc(t('footer_whatsapp')) ?></a>
        </div>
    </div>
    
    <div class="hero-slider-dots">
        <span class="hero-dot is-active" data-index="0"></span>
        <?php for($i=0; $i < count($heroOffers); $i++): ?>
            <span class="hero-dot" data-index="<?= $i+1 ?>"></span>
        <?php endfor; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');
    let currentSlide = 0;
    const intervalTime = 10000; // 10 seconds

    function nextSlide() {
        slides[currentSlide].classList.remove('is-active');
        dots[currentSlide].classList.remove('is-active');
        
        currentSlide = (currentSlide + 1) % slides.length;
        
        slides[currentSlide].classList.add('is-active');
        dots[currentSlide].classList.add('is-active');
    }

    if (slides.length > 1) {
        setInterval(nextSlide, intervalTime);
    }
});
</script>

<section class="trust-strip">
    <div class="container trust-strip__inner">
        <div class="trust-strip__item"><span class="trust-strip__dot" aria-hidden="true"></span> <?= esc(t('trust_1')) ?></div>
        <div class="trust-strip__item"><span class="trust-strip__dot" aria-hidden="true"></span> <?= esc(t('trust_2')) ?></div>
        <div class="trust-strip__item"><span class="trust-strip__dot" aria-hidden="true"></span> <?= esc(t('trust_3')) ?></div>
    </div>
</section>

<section class="section section--categories">
    <div class="container">
        <header class="section-head section-head--left">
            <h2><?= esc(t('home_shop_cat')) ?></h2>
            <p class="section-sub"><?= esc(t('home_shop_cat_sub')) ?></p>
        </header>

        <div class="cat-slider-wrap">
            <div class="category-grid" id="cat-slider">
                <a class="cat-tile" href="<?= esc(url('products.php')) ?>">
                    <span class="cat-tile__media cat-tile__media--all" aria-hidden="true"></span>
                    <span class="cat-tile__body">
                        <span class="cat-tile__title"><?= esc(t('cat_tile_all')) ?></span>
                        <span class="cat-tile__sub"><?= esc(t('cat_tile_all_sub')) ?></span>
                    </span>
                </a>
                <a class="cat-tile" href="<?= esc(url('products.php?cat=women')) ?>">
                    <span class="cat-tile__media cat-tile__media--women" aria-hidden="true"></span>
                    <span class="cat-tile__body">
                        <span class="cat-tile__title"><?= esc(t('cat_tile_women')) ?></span>
                        <span class="cat-tile__sub"><?= esc(t('cat_tile_women_sub')) ?></span>
                    </span>
                </a>
                <a class="cat-tile" href="<?= esc(url('products.php?cat=men')) ?>">
                    <span class="cat-tile__media cat-tile__media--men" aria-hidden="true"></span>
                    <span class="cat-tile__body">
                        <span class="cat-tile__title"><?= esc(t('cat_tile_men')) ?></span>
                        <span class="cat-tile__sub"><?= esc(t('cat_tile_men_sub')) ?></span>
                    </span>
                </a>
                <a class="cat-tile" href="<?= esc(url('products.php?cat=unisex')) ?>">
                    <span class="cat-tile__media cat-tile__media--unisex" aria-hidden="true"></span>
                    <span class="cat-tile__body">
                        <span class="cat-tile__title"><?= esc(t('cat_tile_unisex')) ?></span>
                        <span class="cat-tile__sub"><?= esc(t('cat_tile_unisex_sub')) ?></span>
                    </span>
                </a>
                <a class="cat-tile" href="<?= esc(url('products.php?cat=khinat')) ?>">
                    <span class="cat-tile__media cat-tile__media--khinat" aria-hidden="true" style="background: linear-gradient(135deg, #d4af37, #f4e4bc);"></span>
                    <span class="cat-tile__body">
                        <span class="cat-tile__title">Khanaat</span>
                        <span class="cat-tile__sub">Exclusive Collection</span>
                    </span>
                </a>
            </div>
            <!-- mobile dots -->
            <div class="cat-dots" id="cat-dots" aria-hidden="true">
                <span class="cat-dot is-active" data-index="0"></span>
                <span class="cat-dot" data-index="1"></span>
                <span class="cat-dot" data-index="2"></span>
                <span class="cat-dot" data-index="3"></span>
            </div>
        </div>
    </div>
</section>

<section class="section section--features">
    <div class="container">
        <header class="section-head">
            <h2><?= esc(t('features_block_title')) ?></h2>
            <p class="section-sub"><?= esc(t('features_block_lead')) ?></p>
        </header>
        <ul class="feature-pills">
            <li><?= esc(t('feat_1')) ?></li>
            <li><?= esc(t('feat_2')) ?></li>
            <li><?= esc(t('feat_3')) ?></li>
            <li><?= esc(t('feat_4')) ?></li>
            <li><?= esc(t('feat_5')) ?></li>
            <li><?= esc(t('feat_6')) ?></li>
        </ul>
    </div>
</section>

<section class="section section--products-row">
    <div class="container">
        <header class="section-head section-head--row">
            <div>
                <h2><?= esc(t('sec_best_title')) ?></h2>
                <p class="section-sub"><?= esc(t('sec_best_sub')) ?></p>
            </div>
            <a class="btn btn-outline btn--sm" href="<?= esc(url('products.php')) ?>"><?= esc(t('see_all')) ?></a>
        </header>
        <div class="product-grid product-grid--row">
            <?php foreach ($bestsellers as $p): ?>
                <?php $showBestseller = true;
                require __DIR__ . '/includes/partials/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--products-row section--alt">
    <div class="container">
        <header class="section-head section-head--row">
            <div>
                <h2><?= esc(t('sec_winter_title')) ?></h2>
                <p class="section-sub"><?= esc(t('sec_winter_sub')) ?></p>
            </div>
            <a class="btn btn-outline btn--sm" href="<?= esc(url('products.php')) ?>"><?= esc(t('see_all')) ?></a>
        </header>
        <div class="product-grid product-grid--row">
            <?php foreach ($winterPicks as $p): ?>
                <?php $showBestseller = false;
                require __DIR__ . '/includes/partials/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--products-row">
    <div class="container">
        <header class="section-head section-head--row">
            <div>
                <h2><?= esc(t('sec_summer_title')) ?></h2>
                <p class="section-sub"><?= esc(t('sec_summer_sub')) ?></p>
            </div>
            <a class="btn btn-outline btn--sm" href="<?= esc(url('products.php')) ?>"><?= esc(t('see_all')) ?></a>
        </header>
        <div class="product-grid product-grid--row">
            <?php foreach ($summerPicks as $p): ?>
                <?php $showBestseller = false;
                require __DIR__ . '/includes/partials/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<section class="section section--faq" id="faq">
    <div class="container narrow-wide">
        <header class="section-head">
            <h2><?= esc(t('faq_title')) ?></h2>
            <p class="section-sub"><?= esc(t('faq_lead')) ?></p>
        </header>
        <div class="faq-list">
            <?php
            $faqs = get_all_faqs();
            $currLang = current_lang();
            foreach ($faqs as $f):
                $q = ($currLang === 'ar' ? $f['question_ar'] : $f['question_en']) ?: $f['question_en'];
                $a = ($currLang === 'ar' ? $f['answer_ar'] : $f['answer_en']) ?: $f['answer_en'];
            ?>
                <details class="faq-item">
                    <summary class="faq-item__summary"><?= esc($q) ?></summary>
                    <div class="faq-item__body"><?= esc($a) ?></div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>



<?php require __DIR__ . '/includes/footer.php'; ?>
