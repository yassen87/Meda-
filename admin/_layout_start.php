<?php
declare(strict_types=1);
$pageTitle = $pageTitle ?? t('admin_dashboard');
$htmlLang = current_lang();
$htmlDir = is_rtl() ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?= esc($htmlLang) ?>" dir="<?= esc($htmlDir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>(function(){try{if(localStorage.getItem('medal-theme')==='dark')document.documentElement.classList.add('dark');}catch(e){}})();</script>
    <title><?= esc($pageTitle) ?> — <?= esc(t('admin_title_suffix')) ?></title>
    <link rel="stylesheet" href="<?= esc(admin_asset('assets/css/admin.css?v=1.1')) ?>">
</head>
<body class="admin-body">

<header class="admin-mobile-header" aria-label="Mobile menu">
    <button type="button" class="admin-mobile-toggle" id="admin-nav-open" aria-expanded="false" aria-controls="admin-sidebar">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
    </button>
    <p class="admin-mobile-brand">
        <a href="<?= esc(admin_url('index.php')) ?>">
            <img src="<?= esc(admin_asset('assets/img/logo.png')) ?>" alt="<?= esc(t('admin_brand')) ?>" style="height: 40px; width: auto; vertical-align: middle;">
        </a>
    </p>
</header>

<div class="admin-shell">
<aside class="admin-nav" id="admin-sidebar" aria-label="<?= esc(t('admin_nav_aria')) ?>">
    <div class="admin-nav-header">
        <p class="admin-brand">
            <a href="<?= esc(admin_url('index.php')) ?>">
                <img src="<?= esc(admin_asset('assets/img/logo.png')) ?>" alt="<?= esc(t('admin_brand')) ?>" style="width: 100%; max-width: 180px; height: auto;">
            </a>
        </p>
        <button type="button" class="admin-nav-close" id="admin-nav-close" aria-label="Close menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <nav>
        <a href="<?= esc(admin_url('index.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 8px;"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            <?= esc(t('admin_nav_dashboard')) ?>
        </a>
        <?php if (admin_has_permission('orders')): ?>
        <a href="<?= esc(admin_url('orders.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 8px;"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
            <?= esc(t('admin_nav_orders')) ?>
        </a>
        <?php endif; ?>
        <?php if (admin_has_permission('products')): ?>
        <a href="<?= esc(admin_url('products.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 8px;"><path d="m15 5 4 4"></path><path d="M13 3.832a1.988 1.988 0 0 0-2 0l-7 4.2a2 2 0 0 0-1 1.732V19a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9.764a2 2 0 0 0-1-1.732l-7-4.2Z"></path></svg>
            <?= esc(t('admin_nav_products')) ?>
        </a>
        <a href="<?= esc(admin_url('internal_products.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 8px;"><path d="M20 12V8H4v4"></path><path d="M2 12h20"></path><path d="M7 12v10"></path><path d="M17 12v10"></path><path d="M2 8h20"></path></svg>
            <?= esc(t('admin_nav_internal_products')) ?>
        </a>
        <?php endif; ?>
        <?php if (admin_has_permission('categories')): ?>
        <a href="<?= esc(admin_url('categories.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 8px;"><path d="M21 3.6v16.8a.6.6 0 0 1-.6.6H3.6a.6.6 0 0 1-.6-.6V3.6a.6.6 0 0 1 .6-.6h16.8a.6.6 0 0 1 .6.6Z"></path><path d="M3 9h18"></path><path d="M3 15h18"></path><path d="M9 3v18"></path><path d="M15 3v18"></path></svg>
            <?= esc(t('admin_nav_categories')) ?>
        </a>
        <?php endif; ?>
        <?php if (admin_has_permission('promo_codes')): ?>
        <a href="<?= esc(admin_url('promo_codes.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 8px;"><circle cx="7.5" cy="7.5" r="2.5"></circle><circle cx="16.5" cy="16.5" r="2.5"></circle><line x1="21" y1="3" x2="3" y2="21"></line></svg>
            <?= esc(t('admin_nav_promo_codes')) ?>
        </a>
        <?php endif; ?>
        <?php if (admin_has_permission('clients')): ?>
        <a href="<?= esc(admin_url('clients.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 8px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M17 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <?= esc(t('admin_nav_clients')) ?>
        </a>
        <?php endif; ?>
        <?php if (admin_has_permission('messages')): ?>
        <a href="<?= esc(admin_url('messages.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 8px;"><path d="M22 13V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h9"></path><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path><path d="M19 16v6"></path><path d="m16 19 3 3 3-3"></path></svg>
            <?= esc(t('admin_nav_messages')) ?>
        </a>
        <?php endif; ?>
        <?php if (admin_has_permission('settings')): ?>
        <a href="<?= esc(admin_url('shipping.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 8px;"><path d="M10 17h.01"></path><path d="M3.4 12.1a2 2 0 0 1 1.2-3.3l11-2.2a2 2 0 0 1 2.3 2l.1 8.4a2 2 0 0 1-2 2H5.4a2 2 0 0 1-2-1.9V12.1Z"></path><path d="M14 7V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v3"></path><circle cx="7" cy="17" r="2"></circle><circle cx="17" cy="17" r="2"></circle></svg>
            <?= esc(t('admin_nav_shipping')) ?>
        </a>
        <a href="<?= esc(admin_url('faqs.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 8px;"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><path d="M12 17h.01"></path></svg>
            <?= esc(t('admin_nav_faqs')) ?>
        </a>
        <a href="<?= esc(admin_url('offers.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 8px;"><path d="M12 2v20"></path><path d="m17 5-5-3-5 3"></path><path d="m17 19-5 3-5-3"></path><path d="M2 12h20"></path></svg>
            <?= current_lang() === 'ar' ? 'إدارة العروض' : 'Manage Offers' ?>
        </a>
        <a href="<?= esc(admin_url('settings.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 8px;"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            <?= esc(t('admin_nav_settings')) ?>
        </a>
        <a href="<?= esc(admin_url('admins.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 8px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            <?= esc(t('admin_nav_admins')) ?>
        </a>
        <?php endif; ?>
        <a href="<?= esc(storefront_url('index.php')) ?>" target="_blank" rel="noopener">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 8px;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
            <?= esc(t('admin_nav_view_site')) ?>
        </a>
        <a href="<?= esc(admin_url('logout.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 8px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <?= esc(t('admin_nav_logout')) ?>
        </a>
    </nav>
    <p class="admin-lang-switch" role="group" aria-label="<?= esc(t('lang_switch')) ?>">
        <a href="<?= esc(lang_switch_url('en')) ?>"<?= current_lang() === 'en' ? ' class="is-current"' : '' ?>><?= esc(t('lang_en')) ?></a>
        <span class="admin-lang-sep" aria-hidden="true">·</span>
        <a href="<?= esc(lang_switch_url('ar')) ?>"<?= current_lang() === 'ar' ? ' class="is-current"' : '' ?>><?= esc(t('lang_ar')) ?></a>
    </p>
    <button type="button" class="admin-theme-toggle" id="admin-theme-toggle"
        data-to-daylight="<?= esc(t('theme_to_daylight')) ?>"
        data-to-dark="<?= esc(t('theme_to_dark')) ?>"
        aria-label="<?= esc(t('theme_to_daylight')) ?>">
        <span class="admin-theme-toggle__sun" aria-hidden="true">☀</span>
        <span class="admin-theme-toggle__moon" aria-hidden="true">☾</span>
    </button>
</aside>

<div class="admin-nav-backdrop" id="admin-nav-backdrop"></div>

<script>
    (function() {
        const btnOpen = document.getElementById('admin-nav-open');
        const btnClose = document.getElementById('admin-nav-close');
        const sidebar = document.getElementById('admin-sidebar');
        const backdrop = document.getElementById('admin-nav-backdrop');
        const body = document.body;
        
        function openNav() {
            sidebar.classList.add('is-open');
            body.classList.add('nav-open');
            btnOpen.setAttribute('aria-expanded', 'true');
        }
        
        function closeNav() {
            sidebar.classList.remove('is-open');
            body.classList.remove('nav-open');
            btnOpen.setAttribute('aria-expanded', 'false');
        }

        if (btnOpen && btnClose && sidebar && backdrop) {
            btnOpen.addEventListener('click', openNav);
            btnClose.addEventListener('click', closeNav);
            backdrop.addEventListener('click', closeNav);
        }
    })();
</script>

<main class="admin-main">
