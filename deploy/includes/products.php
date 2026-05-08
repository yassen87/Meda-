<?php
declare(strict_types=1);

require_once __DIR__ . '/product_translations.php';
require_once __DIR__ . '/db.php';

/**
 * @return array<int, array{id:int,name:string,slug:string,price:float,category:string,notes:string,description:string,image:string,season:string,bestseller:bool}>
 */
function get_products_static(): array
{
    return [
        [
            'id' => 1,
            'name' => 'Velvet Oud',
            'slug' => 'velvet-oud',
            'price' => 189.00,
            'category' => 'unisex',
            'season' => 'winter',
            'bestseller' => true,
            'notes' => 'Oud, rose, amber, sandalwood',
            'description' => 'A deep, resinous oud wrapped in velvet rose and warm amber. Designed for evenings that linger.',
            'image' => 'velvet-oud',
        ],
        [
            'id' => 2,
            'name' => 'Citrus Doré',
            'slug' => 'citrus-dore',
            'price' => 125.00,
            'category' => 'women',
            'season' => 'summer',
            'bestseller' => true,
            'notes' => 'Bergamot, neroli, white musk',
            'description' => 'Sunlit citrus and soft neroli over a clean musk base. Effortlessly bright and refined.',
            'image' => 'citrus-dore',
        ],
        [
            'id' => 3,
            'name' => 'Noir Épicé',
            'slug' => 'noir-epice',
            'price' => 165.00,
            'category' => 'men',
            'season' => 'winter',
            'bestseller' => true,
            'notes' => 'Black pepper, cedar, vetiver, tonka',
            'description' => 'Spiced opening, woody heart, and a smooth tonka dry-down. Confident without shouting.',
            'image' => 'noir-epice',
        ],
        [
            'id' => 4,
            'name' => 'Jardin de Minuit',
            'slug' => 'jardin-de-minuit',
            'price' => 142.00,
            'category' => 'women',
            'season' => 'summer',
            'bestseller' => false,
            'notes' => 'Jasmine, tuberose, patchouli',
            'description' => 'White florals at night—jasmine and tuberose grounded by earthy patchouli.',
            'image' => 'jardin-minuit',
        ],
        [
            'id' => 5,
            'name' => 'Mer & Sel',
            'slug' => 'mer-sel',
            'price' => 118.00,
            'category' => 'unisex',
            'season' => 'summer',
            'bestseller' => false,
            'notes' => 'Sea salt, driftwood, ambrette',
            'description' => 'Coastal air, sun-warmed wood, and a whisper of skin. Minimal and memorable.',
            'image' => 'mer-sel',
        ],
        [
            'id' => 6,
            'name' => 'Cuir Royal',
            'slug' => 'cuir-royal',
            'price' => 198.00,
            'category' => 'men',
            'season' => 'winter',
            'bestseller' => false,
            'notes' => 'Leather, iris, saffron, oakmoss',
            'description' => 'Supple leather with iris powder and saffron heat. A modern classic.',
            'image' => 'cuir-royal',
        ],
        [
            'id' => 7,
            'name' => 'Khinat Oud Special',
            'slug' => 'khinat-oud-special',
            'price' => 250.00,
            'category' => 'khinat',
            'season' => 'winter',
            'bestseller' => true,
            'notes' => 'Premium oud, saffron, musk, amber',
            'description' => 'Exclusive khinat blend with premium oud and luxury notes. Perfect for special occasions.',
            'image' => 'khinat-oud-special',
        ],
        [
            'id' => 8,
            'name' => 'Khinat Rose Gold',
            'slug' => 'khinat-rose-gold',
            'price' => 185.00,
            'category' => 'khinat',
            'season' => 'summer',
            'bestseller' => false,
            'notes' => 'Turkish rose, gold amber, white musk',
            'description' => 'Elegant rose and gold amber combination. Modern luxury in every drop.',
            'image' => 'khinat-rose-gold',
        ],
        [
            'id' => 9,
            'name' => 'Khinat Royal Blend',
            'slug' => 'khinat-royal-blend',
            'price' => 320.00,
            'category' => 'khinat',
            'season' => 'both',
            'bestseller' => true,
            'notes' => 'Royal oud, vanilla, sandalwood, spices',
            'description' => 'A royal blend fit for royalty. Complex and sophisticated fragrance profile.',
            'image' => 'khinat-royal-blend',
        ],
    ];
}

/** @return list<array<string, mixed>> */
function get_products_from_db(): array
{
    $pdo = medal_pdo();
    if ($pdo === null) {
        return [];
    }
    try {
        $sql = 'SELECT p.*,
            (SELECT id FROM product_variants WHERE product_id = p.id ORDER BY sort_order ASC, id ASC LIMIT 1) AS default_variant_id,
            (SELECT price FROM product_variants WHERE product_id = p.id ORDER BY sort_order ASC, id ASC LIMIT 1) AS price
            FROM products p WHERE p.active = 1 ORDER BY p.sort_order ASC, p.id ASC';
        $rows = $pdo->query($sql)->fetchAll();
    } catch (Throwable) {
        return [];
    }
    $out = [];
    foreach ($rows as $r) {
        $out[] = map_db_product_row($r);
    }
    return $out;
}

/** @param array<string, mixed> $r */
function map_db_product_row(array $r): array
{
    return [
        'id' => (int) $r['id'],
        'slug' => (string) $r['slug'],
        'category' => (string) $r['category'],
        'season' => (string) $r['season'],
        'bestseller' => !empty($r['is_bestseller']),
        'is_offer' => !empty($r['is_offer']),
        'image' => (string) $r['primary_image_key'],
        'price' => isset($r['price']) ? (float) $r['price'] : 0.0,
        'default_variant_id' => isset($r['default_variant_id']) ? (int) $r['default_variant_id'] : 0,
        'name_en' => (string) $r['name_en'],
        'name_ar' => (string) $r['name_ar'],
        'notes_en' => (string) ($r['notes_en'] ?? ''),
        'notes_ar' => (string) ($r['notes_ar'] ?? ''),
        'description_en' => (string) $r['description_en'],
        'description_ar' => (string) $r['description_ar'],
        'is_db' => true,
    ];
}

/** @return array<int, array<string, mixed>> */
function get_products(): array
{
    $db = get_products_from_db();
    if ($db !== []) {
        return $db;
    }
    return get_products_static();
}

function get_product_by_id(int $id): ?array
{
    $pdo = medal_pdo();
    if ($pdo !== null) {
        try {
            $st = $pdo->prepare(
                'SELECT p.*,
                (SELECT id FROM product_variants WHERE product_id = p.id ORDER BY sort_order ASC, id ASC LIMIT 1) AS default_variant_id,
                (SELECT price FROM product_variants WHERE product_id = p.id ORDER BY sort_order ASC, id ASC LIMIT 1) AS price
                FROM products p WHERE p.id = ? AND p.active = 1'
            );
            $st->execute([$id]);
            $r = $st->fetch();
            if ($r !== false) {
                return map_db_product_row($r);
            }
        } catch (Throwable) {
        }
    }
    foreach (get_products_static() as $p) {
        if ($p['id'] === $id) {
            return $p;
        }
    }
    return null;
}

function get_product_by_slug(string $slug): ?array
{
    foreach (get_products() as $p) {
        if (($p['slug'] ?? '') === $slug) {
            return $p;
        }
    }
    return null;
}

/** @return list<array{id:int,label_en:string,label_ar:string,price:float,compare_at_price:?float,sort_order:int}> */
function get_product_variants(int $productId): array
{
    $pdo = medal_pdo();
    if ($pdo === null) {
        return [];
    }
    try {
        $st = $pdo->prepare('SELECT id, label_en, label_ar, price, compare_at_price, stock, sort_order FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, id ASC');
        $st->execute([$productId]);
        $rows = $st->fetchAll();
    } catch (Throwable) {
        return [];
    }
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'label_en' => (string) $r['label_en'],
            'label_ar' => (string) $r['label_ar'],
            'price' => (float) $r['price'],
            'compare_at_price' => isset($r['compare_at_price']) && $r['compare_at_price'] !== null ? (float) $r['compare_at_price'] : null,
            'stock' => (int) ($r['stock'] ?? 0),
            'sort_order' => (int) $r['sort_order'],
        ];
    }
    return $out;
}

/** @param array<string, mixed> $p */
function localize_product(array $p, ?string $lang = null): array
{
    $lang = $lang ?? current_lang();
    if (!empty($p['is_db']) || (isset($p['name_en']) && isset($p['name_ar']))) {
        $isAr = $lang === 'ar';
        $out = $p;
        $out['name'] = $isAr ? (string) $p['name_ar'] : (string) $p['name_en'];
        $out['notes'] = $isAr ? (string) ($p['notes_ar'] ?? '') : (string) ($p['notes_en'] ?? '');
        $out['description'] = $isAr ? (string) $p['description_ar'] : (string) $p['description_en'];
        unset($out['name_en'], $out['name_ar'], $out['notes_en'], $out['notes_ar'], $out['description_en'], $out['description_ar'], $out['is_db']);
        return $out;
    }
    if ($lang !== 'ar') {
        return $p;
    }
    $tr = product_translations_ar()[$p['id']] ?? null;
    return $tr !== null ? array_merge($p, $tr) : $p;
}

/** @return list<array<string, mixed>> */
function get_products_localized(): array
{
    return array_map(static fn (array $p) => localize_product($p), get_products());
}

/** @return array<string, mixed>|null */
function get_product_by_id_localized(int $id): ?array
{
    $p = get_product_by_id($id);
    return $p !== null ? localize_product($p) : null;
}

/** @return array{price:float, variant_id:int}|null */
function resolve_product_variant(int $productId, ?int $variantId): ?array
{
    $pdo = medal_pdo();
    if ($pdo !== null) {
        try {
            if ($variantId !== null && $variantId > 0) {
                $st = $pdo->prepare('SELECT id, price FROM product_variants WHERE id = ? AND product_id = ?');
                $st->execute([$variantId, $productId]);
                $r = $st->fetch();
                if ($r !== false) {
                    return ['price' => (float) $r['price'], 'variant_id' => (int) $r['id']];
                }
            }
            $st = $pdo->prepare('SELECT id, price FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1');
            $st->execute([$productId]);
            $r = $st->fetch();
            if ($r !== false) {
                return ['price' => (float) $r['price'], 'variant_id' => (int) $r['id']];
            }
        } catch (Throwable) {
            return null;
        }
        return null;
    }
    $p = get_product_by_id($productId);
    if ($p === null) {
        return null;
    }
    return ['price' => (float) $p['price'], 'variant_id' => 0];
}

/** @return array<string, mixed>|null */
function get_cart_line_product(int $productId, ?int $variantId): ?array
{
    $p = get_product_by_id($productId);
    if ($p === null) {
        return null;
    }
    $rv = resolve_product_variant($productId, $variantId);
    if ($rv === null) {
        return null;
    }
    $p['price'] = $rv['price'];
    $p['cart_variant_id'] = $rv['variant_id'];
    return localize_product($p);
}

function category_label(string $category): string
{
    return t('cat_' . $category);
}

function count_products_in_category(string $category): int
{
    if ($category === 'all') {
        return count(get_products());
    }
    return count(array_filter(get_products(), static fn (array $p) => $p['category'] === $category));
}

function product_matches_season(array $p, string $season): bool
{
    $s = $p['season'] ?? 'both';
    return $s === 'both' || $s === $season;
}

/** @return list<array<string, mixed>> */
function get_products_localized_filtered(callable $predicate): array
{
    return array_values(array_filter(get_products_localized(), $predicate));
}

/** @return list<array<string, mixed>> */
function get_bestsellers_localized(int $limit = 4): array
{
    $list = get_products_localized_filtered(static fn (array $p) => !empty($p['bestseller']));
    if ($list === []) {
        $list = array_slice(get_products_localized(), 0, $limit);
    }
    return array_slice($list, 0, $limit);
}

/** @return list<array<string, mixed>> */
function get_seasonal_localized(string $season, int $limit = 4): array
{
    $list = get_products_localized_filtered(static fn (array $p) => product_matches_season($p, $season));
    return array_slice($list, 0, $limit);
}
