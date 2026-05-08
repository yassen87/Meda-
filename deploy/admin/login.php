<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/admin_bootstrap.php';

if (admin_is_logged_in()) {
    header('Location: ' . admin_url('index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $user = trim((string) ($_POST['username'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    $pdo = medal_pdo();
    if ($pdo === null) {
        $error = t('admin_err_db_not_configured');
    } elseif ($user === '' || $pass === '') {
        $error = t('admin_err_enter_credentials');
    } else {
        $st = $pdo->prepare('SELECT id, password_hash FROM admin_users WHERE username = ?');
        $st->execute([$user]);
        $row = $st->fetch();
        if ($row !== false && password_verify($pass, (string) $row['password_hash'])) {
            admin_login((int) $row['id']);
            header('Location: ' . admin_url('index.php'));
            exit;
        }
        $error = t('admin_err_invalid_credentials');
    }
}
$htmlLang = current_lang();
$htmlDir = is_rtl() ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?= esc($htmlLang) ?>" dir="<?= esc($htmlDir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>(function(){try{if(localStorage.getItem('medal-theme')==='dark')document.documentElement.classList.add('dark');}catch(e){}})();</script>
    <title><?= esc(t('admin_login_title')) ?> — <?= esc(t('admin_title_suffix')) ?></title>
    <link rel="stylesheet" href="<?= esc(admin_asset('assets/css/admin.css?v=1.1')) ?>">
</head>
<body class="admin-body">
<div class="admin-login-wrap">
    <div class="admin-login-card">
        <h1><?= esc(t('admin_login_heading')) ?></h1>
        <p class="admin-lang-switch" role="group" aria-label="<?= esc(t('lang_switch')) ?>" style="margin:0 0 0.5rem">
            <a href="<?= esc(lang_switch_url('en')) ?>"<?= current_lang() === 'en' ? ' class="is-current"' : '' ?>><?= esc(t('lang_en')) ?></a>
            <span class="admin-lang-sep" aria-hidden="true">·</span>
            <a href="<?= esc(lang_switch_url('ar')) ?>"<?= current_lang() === 'ar' ? ' class="is-current"' : '' ?>><?= esc(t('lang_ar')) ?></a>
        </p>
        <p style="margin:0 0 1rem">
            <button type="button" class="admin-theme-toggle" id="admin-theme-toggle"
                data-to-daylight="<?= esc(t('theme_to_daylight')) ?>"
                data-to-dark="<?= esc(t('theme_to_dark')) ?>"
                aria-label="<?= esc(t('theme_to_daylight')) ?>">
                <span class="admin-theme-toggle__sun" aria-hidden="true">☀</span>
                <span class="admin-theme-toggle__moon" aria-hidden="true">☾</span>
            </button>
        </p>
        <?php if ($error !== ''): ?>
            <div class="admin-error"><?= esc($error) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= esc(admin_url('login.php')) ?>">
            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
            <div class="admin-form">
                <label for="username"><?= esc(t('admin_login_username')) ?></label>
                <input type="text" id="username" name="username" required autocomplete="username" value="<?= esc(trim((string) ($_POST['username'] ?? ''))) ?>">
                <label for="password"><?= esc(t('admin_login_password')) ?></label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
                <p style="margin-top:1rem"><button type="submit" class="btn-admin"><?= esc(t('admin_login_submit')) ?></button></p>
            </div>
        </form>
        <p class="admin-muted" style="margin-top:1rem"><?= esc(t('admin_login_hint')) ?></p>
    </div>
</div>
<script src="<?= esc(admin_asset('assets/js/admin-theme.js?v=1.1')) ?>" defer></script>
</body>
</html>
