<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/products.php';

$pageTitle = t('page_collection');
$filter = $_GET['cat'] ?? 'all';
$valid = ['all', 'women', 'men', 'unisex', 'khinat', 'offers'];
if (!in_array($filter, $valid, true)) {
    $filter = 'all';
}

$searchQuery = trim($_GET['q'] ?? '');

$products = get_products_localized();
if ($filter === 'offers') {
    $products = array_values(array_filter($products, static fn ($p) => !empty($p['is_offer'])));
} elseif ($filter !== 'all') {
    $products = array_values(array_filter($products, static fn ($p) => $p['category'] === $filter));
}

if ($searchQuery !== '') {
    $products = array_values(array_filter($products, static function ($p) use ($searchQuery) {
        $name = $p['name'] ?? '';
        $desc = $p['description'] ?? '';
        $notes = $p['notes'] ?? '';
        
        if (function_exists('mb_stripos')) {
            return mb_stripos($name, $searchQuery, 0, 'UTF-8') !== false ||
                   mb_stripos($desc, $searchQuery, 0, 'UTF-8') !== false ||
                   mb_stripos($notes, $searchQuery, 0, 'UTF-8') !== false;
        }
        
        // Fallback if mbstring is not installed
        $sq = strtolower($searchQuery);
        return stripos($name, $sq) !== false || 
               stripos($desc, $sq) !== false || 
               stripos($notes, $sq) !== false ||
               strpos($name, $searchQuery) !== false ||
               strpos($desc, $searchQuery) !== false ||
               strpos($notes, $searchQuery) !== false;
    }));
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--compact">
    <div class="container">
        <h1><?= esc(t('page_collection')) ?></h1>
        <?php if ($searchQuery !== ''): ?>
            <p class="page-lead"><?= esc(str_replace(':query', $searchQuery, t('search_results_for'))) ?></p>
        <?php else: ?>
            <p class="page-lead"><?= esc(t('collection_lead')) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="collection-controls">
            <form method="GET" action="<?= esc(url('products.php')) ?>" class="collection-search">
                <div class="search-input-wrap">
                    <input type="search" name="q" value="<?= esc($searchQuery) ?>" placeholder="<?= esc(t('search_placeholder')) ?>">
                    <button type="submit" class="search-submit" aria-label="<?= esc(t('search_button')) ?>">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path></svg>
                    </button>
                </div>
                <?php if ($filter !== 'all'): ?>
                    <input type="hidden" name="cat" value="<?= esc($filter) ?>">
                <?php endif; ?>
            </form>
            
            <div class="filters" role="tablist" aria-label="<?= esc(t('filter_label')) ?>">
                <a class="filter-pill<?= $filter === 'all' ? ' is-active' : '' ?>" href="<?= esc(url('products.php' . ($searchQuery ? '?q=' . urlencode($searchQuery) : ''))) ?>"><?= esc(t('filter_all')) ?></a>
                <a class="filter-pill<?= $filter === 'offers' ? ' is-active' : '' ?>" href="<?= esc(url('products.php?cat=offers' . ($searchQuery ? '&q=' . urlencode($searchQuery) : ''))) ?>"><?= esc(t('filter_offers')) ?></a>
                <a class="filter-pill<?= $filter === 'women' ? ' is-active' : '' ?>" href="<?= esc(url('products.php?cat=women' . ($searchQuery ? '&q=' . urlencode($searchQuery) : ''))) ?>"><?= esc(t('filter_women')) ?></a>
                <a class="filter-pill<?= $filter === 'men' ? ' is-active' : '' ?>" href="<?= esc(url('products.php?cat=men' . ($searchQuery ? '&q=' . urlencode($searchQuery) : ''))) ?>"><?= esc(t('filter_men')) ?></a>
                <a class="filter-pill<?= $filter === 'unisex' ? ' is-active' : '' ?>" href="<?= esc(url('products.php?cat=unisex' . ($searchQuery ? '&q=' . urlencode($searchQuery) : ''))) ?>"><?= esc(t('filter_unisex')) ?></a>
            </div>
        </div>

        <?php if ($products === []): ?>
            <p class="empty-state"><?= $searchQuery !== '' ? esc(t('search_no_results')) : esc(t('empty_category')) ?></p>
        <?php else: ?>
            <div class="product-grid product-grid--large">
                <?php foreach ($products as $p): ?>
                    <?php $showBestseller = false; require __DIR__ . '/includes/partials/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
