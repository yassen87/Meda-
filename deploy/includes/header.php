<?php
declare(strict_types=1);
$pageTitle = $pageTitle ?? site_name();
$bodyClass = trim('theme-gulf ' . ($bodyClass ?? '') . ' lang-' . preg_replace('/[^a-z]/', '', current_lang()));
$htmlLang = current_lang();
$htmlDir = is_rtl() ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?= esc($htmlLang) ?>" dir="<?= esc($htmlDir) ?>">
<head>
    <meta charset="UTF-8">
    <script>
    try {
        if (localStorage.getItem("medal-theme") !== "dark") {
            document.documentElement.classList.add("daylight");
        }
    } catch (e) {}
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?> — <?= esc(site_name()) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=El+Messiri:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= esc(url('assets/css/style.css?v=' . time())) ?>">
    <link rel="stylesheet" href="<?= esc(url('assets/css/theme-daylight.css')) ?>">
</head>
<body class="<?= esc($bodyClass) ?>">
    <a class="skip-link" href="#main"><?= esc(t('skip_content')) ?></a>
    <div class="announce-bar" role="note">
        <div class="container announce-bar__inner">
            <span class="announce-bar__spark" aria-hidden="true">✦</span>
            <p class="announce-bar__text"><?= esc(get_setting('announce_shipping')) ?></p>
        </div>
    </div>
    <header class="site-header">
        <div class="container header-inner">
            <a class="logo" href="<?= esc(url('index.php')) ?>">
                <img src="<?= esc(url('assets/img/logo.png')) ?>" alt="<?= esc(site_name()) ?>" style="height: 55px; width: auto; vertical-align: middle;">
            </a>
            <form method="GET" action="<?= esc(url('products.php')) ?>" class="header-search">
                <input type="search" name="q" placeholder="<?= esc(t('search_placeholder')) ?>" class="header-search__input">
                <button type="submit" class="header-search__btn" aria-label="<?= esc(t('search_button')) ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
            </form>
            <div class="header-right">
                <nav id="site-nav" class="site-nav" aria-label="<?= esc(t('nav_main')) ?>">
                    <ul>
                        <li><a href="<?= esc(url('index.php')) ?>"><?= esc(t('nav_home')) ?></a></li>
                        <li><a href="<?= esc(url('products.php')) ?>"><?= esc(t('nav_collection')) ?></a></li>
                        <li><a href="<?= esc(url('about.php')) ?>"><?= esc(t('nav_story')) ?></a></li>
                        <li><a href="<?= esc(url('contact.php')) ?>"><?= esc(t('nav_contact')) ?></a></li>
                        <?php if (isset($_SESSION['client_id'])): ?>
                            <li>
                                <a href="<?= esc(url('client/dashboard.php')) ?>" class="nav-account-link" style="display:inline-flex;align-items:center;gap:.4em;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                                    <span><?= esc(t('nav_account')) ?></span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-auth-group">
                                <a href="<?= esc(url('client/login.php')) ?>" class="nav-login-link" style="display:inline-flex;align-items:center;gap:.35em;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                                    <span><?= esc(t('nav_login')) ?></span>
                                </a>
                                <span class="nav-auth-sep" aria-hidden="true">|</span>
                                <a href="<?= esc(url('client/register.php')) ?>" class="nav-register-btn" style="padding:.3rem .85rem!important;font-size:.72rem!important;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg>
                                    <span><?= esc(t('nav_register')) ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <a href="<?= esc(url('cart.php')) ?>" class="nav-cart">
                                <span class="nav-cart__icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 8h15l-1.5 9H7.5L6 8z"/>
                                        <path d="M6 8 5 3H2"/>
                                        <circle cx="10" cy="20" r="1.25" fill="currentColor" stroke="none"/>
                                        <circle cx="17" cy="20" r="1.25" fill="currentColor" stroke="none"/>
                                    </svg>
                                </span>
                                <!-- Cart label removed to keep only the icon and badge -->
                                <?php if (cart_count() > 0): ?>
                                    <span class="cart-badge"><?= (int) cart_count() ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </nav>
                <div class="lang-switcher" role="group" aria-label="<?= esc(t('lang_switch')) ?>">
                    <a class="lang-switcher__link<?= current_lang() === 'en' ? ' is-active' : '' ?>" href="<?= esc(lang_switch_url('en')) ?>" hreflang="en" lang="en"><?= esc(t('lang_en')) ?></a>
                    <span class="lang-switcher__sep" aria-hidden="true">|</span>
                    <a class="lang-switcher__link<?= current_lang() === 'ar' ? ' is-active' : '' ?>" href="<?= esc(lang_switch_url('ar')) ?>" hreflang="ar" lang="ar"><?= esc(t('lang_ar')) ?></a>
                </div>
                <button type="button" class="theme-toggle" id="theme-toggle"
                    data-to-daylight="<?= esc(t('theme_to_daylight')) ?>"
                    data-to-dark="<?= esc(t('theme_to_dark')) ?>"
                    aria-label="<?= esc(t('theme_to_daylight')) ?>">
                    <span class="theme-toggle__sun" aria-hidden="true">☀</span>
                    <span class="theme-toggle__moon" aria-hidden="true">☾</span>
                </button>
                <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="site-nav" aria-label="<?= esc(t('nav_open')) ?>" data-nav-open="<?= esc(t('nav_open')) ?>" data-nav-close="<?= esc(t('nav_close')) ?>">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>
    <main id="main">
