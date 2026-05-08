<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
cart_normalize_session();

require_once __DIR__ . '/locale.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail_helper.php';
locale_bootstrap();

define('SITE_NAME', t('site_name'));

/** E.164 without + — edit for your WhatsApp Business number */
const CONTACT_WHATSAPP_E164 = '201000000000';
/** Display / tel: value */
const CONTACT_PHONE_TEL = '+201000000000';

function contact_whatsapp_url(): string
{
    return 'https://wa.me/' . CONTACT_WHATSAPP_E164;
}

function contact_phone_href(): string
{
    return 'tel:' . str_replace(' ', '', CONTACT_PHONE_TEL);
}

function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $rootAbs = str_replace('\\', '/', dirname(__DIR__));
    $scriptAbs = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $scriptWeb = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    if ($scriptAbs === '' || $scriptWeb === '') {
        return '';
    }

    $rel = str_replace($rootAbs, '', $scriptAbs);
    $basePath = substr($scriptWeb, 0, -strlen($rel));
    $base = rtrim($basePath, '/');
    return $base;
}

function url(string $path): string
{
    $path = ltrim($path, '/');
    $file = $path;
    $q = [];
    if (strpos($path, '?') !== false) {
        [$file, $qs] = explode('?', $path, 2);
        parse_str($qs, $q);
    }
    $scheme = 'http';
    if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
        $scheme = 'https';
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $prefix = base_path() !== '' ? base_path() . '/' : '/';
    $full = $scheme . '://' . $host . $prefix . $file;

    $fileLower = strtolower($file);
    $isStatic = (strpos($fileLower, 'assets/') === 0)
        || (bool) preg_match('/\.(css|js|mjs|map|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|eot)$/i', $file);

    if (!$isStatic) {
        $q['lang'] = current_lang();
    } else {
        unset($q['lang']);
    }

    if ($q === []) {
        return $full;
    }
    return $full . '?' . http_build_query($q);
}

function esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function product_image_style(?string $imageKey): string
{
    if (!$imageKey || $imageKey === 'default') {
        return '';
    }
    if (strpos($imageKey, 'http://') === 0 || strpos($imageKey, 'https://') === 0) {
        return ' style="background-image: url(\'' . esc($imageKey) . '\'); background-size: cover; background-position: center;"';
    }
    // If it starts with img_ or has a dot, it's an upload
    if (strpos($imageKey, 'img_') === 0 || strpos($imageKey, '.') !== false) {
        return ' style="background-image: url(\'' . esc(url('assets/uploads/' . $imageKey)) . '\'); background-size: cover; background-position: center;"';
    }
    // Static assets
    $path = url('assets/img/' . $imageKey . '.jpg');
    return ' style="background-image: url(\'' . esc($path) . '\'); background-size: cover; background-position: center;"';
}

function product_image_class(?string $imageKey, string $baseClass = 'product-visual'): string
{
    return $baseClass;
}

function cart_line_key(int $productId, ?int $variantId): string
{
    $v = $variantId ?? 0;
    return $productId . '-' . $v;
}

/** @return array{product_id:int, variant_id:?int} */
function cart_parse_line_key(string $key): array
{
    $parts = explode('-', $key, 2);
    $pid = (int) ($parts[0] ?? 0);
    $vid = isset($parts[1]) ? (int) $parts[1] : 0;
    return ['product_id' => $pid, 'variant_id' => $vid > 0 ? $vid : null];
}

function cart_normalize_session(): void
{
    $c = $_SESSION['cart'];
    if ($c === []) {
        return;
    }
    $firstKey = (string) array_key_first($c);
    if (strpos($firstKey, '-') !== false) {
        return;
    }
    $new = [];
    foreach ($c as $pid => $qty) {
        if (!is_numeric($pid)) {
            continue;
        }
        $new[cart_line_key((int) $pid, null)] = (int) $qty;
    }
    $_SESSION['cart'] = $new;
}

function cart_count(): int
{
    return array_sum($_SESSION['cart'] ?? []);
}

function add_to_cart(int $productId, int $qty = 1, ?int $variantId = null): void
{
    if ($qty < 1) {
        $qty = 1;
    }
    $k = cart_line_key($productId, $variantId);
    if (!isset($_SESSION['cart'][$k])) {
        $_SESSION['cart'][$k] = 0;
    }
    $_SESSION['cart'][$k] += $qty;
}

function remove_cart_line(string $lineKey): void
{
    unset($_SESSION['cart'][$lineKey]);
}

function remove_from_cart(int $productId): void
{
    foreach ($_SESSION['cart'] as $key => $_) {
        $parsed = cart_parse_line_key((string) $key);
        if ($parsed['product_id'] === $productId) {
            unset($_SESSION['cart'][$key]);
        }
    }
}

function format_price(float|int|string $amount): string
{
    return number_format((float)$amount, 2) . ' ' . t('currency');
}

/**
 * Fetch a localized setting from the database, falling back to the translation file.
 */
function get_setting(string $key, ?string $default = null): string
{
    $pdo = medal_pdo();
    if ($pdo !== null) {
        try {
            $st = $pdo->prepare('SELECT setting_value_en, setting_value_ar FROM settings WHERE setting_key = ?');
            $st->execute([$key]);
            $row = $st->fetch();
            if ($row) {
                $val = current_lang() === 'ar' ? $row['setting_value_ar'] : $row['setting_value_en'];
                if ($val !== null && $val !== '') {
                    return $val;
                }
            }
        } catch (Throwable) {
        }
    }
    return $default ?? t($key) ?? '';
}

/**
 * Fetch all FAQs ordered by sort_order.
 */
function get_all_faqs(): array
{
    $pdo = medal_pdo();
    if ($pdo !== null) {
        try {
            $st = $pdo->query('SELECT question_en, question_ar, answer_en, answer_ar FROM faqs ORDER BY sort_order ASC, id ASC');
            return $st->fetchAll();
        } catch (Throwable) {
        }
    }
    return [];
}
