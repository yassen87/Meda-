<?php
declare(strict_types=1);

// Hostinger Database Configuration
// Update these values with your Hostinger database details
const DB_HOST = 'localhost'; // Usually localhost on Hostinger
const DB_NAME = 'your_database_name'; // Replace with your Hostinger database name
const DB_USER = 'your_database_user'; // Replace with your Hostinger database user
const DB_PASS = 'your_database_password'; // Replace with your Hostinger database password

// Base URLs
define('BASE_URL', 'https://yourdomain.com'); // Replace with your domain
define('ADMIN_URL', 'https://yourdomain.com/admin/'); // Replace with your domain

// Security
define('ENCRYPTION_KEY', 'your-32-character-encryption-key-here'); // Generate a secure 32-character key

// Error reporting (set to false in production)
define('DEBUG_MODE', false);

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

// Timezone
date_default_timezone_set('UTC');

// Hostinger-specific settings
if (strpos($_SERVER['HTTP_HOST'], 'hostinger') !== false) {
    // Hostinger-specific configurations
    ini_set('max_execution_time', 300);
    ini_set('memory_limit', '256M');
    ini_set('upload_max_filesize', '10M');
    ini_set('post_max_size', '12M');
}

// Function to get database connection
function medal_pdo(): ?PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log('Database connection failed: ' . $e->getMessage());
        }
        return null;
    }

    return $pdo;
}

// Function to get base URL
function base_url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

// Function to get admin URL
function admin_url(string $path = ''): string
{
    return rtrim(ADMIN_URL, '/') . '/' . ltrim($path, '');
}

// Function to escape HTML
function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Function to get current language
function current_lang(): string
{
    return $_SESSION['lang'] ?? 'en';
}

// Function to check if RTL
function is_rtl(): bool
{
    return current_lang() === 'ar';
}

// Function to get translation
function t(string $key): string
{
    static $translations = null;
    if ($translations === null) {
        $lang = current_lang();
        $file = __DIR__ . "/translations/{$lang}.php";
        if (file_exists($file)) {
            $translations = require $file;
        } else {
            $translations = require __DIR__ . "/translations/en.php";
        }
    }
    return $translations[$key] ?? $key;
}

// Function to get language switch URL
function lang_switch_url(string $lang): string
{
    $current = $_GET;
    $current['lang'] = $lang;
    return '?' . http_build_query($current);
}

// Function to get contact WhatsApp URL
function contact_whatsapp_url(): string
{
    $phone = t('contact_whatsapp_phone');
    return "https://wa.me/{$phone}";
}

// Function to get product image class
function product_image_class(string $imageKey, string $defaultClass = ''): string
{
    if ($imageKey === 'default') {
        return $defaultClass;
    }
    return "product-image product-image--{$imageKey}";
}

// Function to get product image style
function product_image_style(string $imageKey): string
{
    if ($imageKey === 'default') {
        return '';
    }
    
    $imagePath = base_url("assets/images/products/{$imageKey}.jpg");
    if (file_exists(__DIR__ . "/../assets/images/products/{$imageKey}.jpg")) {
        return "background-image: url('{$imagePath}')";
    }
    return '';
}

// Function to get admin asset URL
function admin_asset(string $path): string
{
    return admin_url($path);
}

// Function to get storefront asset URL
function storefront_asset(string $path): string
{
    return base_url($path);
}

// Function to get URL
function url(string $path): string
{
    return base_url($path);
}

// Function to verify admin CSRF token
function admin_verify_csrf(): void
{
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['admin_csrf']) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
}

// Function to get admin CSRF token
function admin_csrf_token(): string
{
    if (!isset($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}

// Function to get admin JS string
function admin_js_string(string $string): string
{
    return json_encode($string, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}

// Function to get admin season label
function admin_season_label(string $season): string
{
    return t('season_' . $season);
}

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set language from GET parameter
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

?>
