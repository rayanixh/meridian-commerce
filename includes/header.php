<?php
if (!defined('APP_NAME')) { http_response_code(403); exit; }

$siteName = setting('site_name', 'Meridian');
$pageTitle = $pageTitle ?? $siteName;
$fullTitle = $pageTitle === $siteName ? $pageTitle : "$pageTitle · $siteName";
$cartCount = cart_count();
$announcement = setting('announcement_text', '');
$logo = site_logo('main');
$fav = favicon_url();
$extraCss = $extraCss ?? [];
$pageJs = $pageJs ?? [];
$bodyClass = $bodyClass ?? 'page-default';
// Page-scoped stylesheet for the storefront homepage. Other pages
// (cart, product, account, checkout, etc.) are not affected.
if ($bodyClass === 'page-storefront') {
    $extraCss[] = 'css/storefront.css';
}

$navItems = [];
try {
    $pdo = db();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM navigation_items WHERE location = 'desktop' AND status = 'active' ORDER BY position ASC");
        $stmt->execute();
        $navItems = $stmt->fetchAll();
    }
} catch (Throwable $e) {}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e(setting('site_description', '')) ?>">
<?php if ($fav): ?><link rel="icon" href="<?= e($fav) ?>"><?php endif; ?>
<link rel="stylesheet" href="<?= asset('css/base.css') ?>">
<link rel="stylesheet" href="<?= asset('css/components.css') ?>">
<link rel="stylesheet" href="<?= asset('css/desktop.css') ?>">
<link rel="stylesheet" href="<?= asset('css/tablet.css') ?>" media="(min-width: 768px) and (max-width: 1023.98px)">
<link rel="stylesheet" href="<?= asset('css/mobile.css') ?>" media="(max-width: 767.98px)">
<?php foreach ($extraCss as $css): ?><link rel="stylesheet" href="<?= asset($css) ?>"><?php endforeach; ?>
<style><?= theme_css_vars() ?></style>
</head>
<body class="<?= e($bodyClass) ?>">
<?php if (!empty($announcement)): ?>
<div class="announcement-bar">
    <div class="container announcement-inner"><span><?= e($announcement) ?></span></div>
</div>
<?php endif; ?>

<header class="site-header" id="siteHeader">
    <div class="container header-inner">
        <button class="icon-btn nav-toggle mobile-tablet-only" aria-label="Menu" data-nav-toggle type="button">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><line x1="3" y1="7" x2="21" y2="7"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="17" x2="21" y2="17"/></svg>
        </button>

        <a href="<?= base_url('index.php') ?>" class="brand">
            <?php if ($logo): ?>
                <img src="<?= e($logo) ?>" alt="<?= e($siteName) ?>" class="brand-logo">
            <?php else: ?>
                <span class="brand-mark">M</span>
            <?php endif; ?>
            <span class="brand-name"><?= e($siteName) ?></span>
        </a>

        <nav class="primary-nav desktop-only" aria-label="Primary">
            <?php foreach ($navItems as $ni): ?>
            <a href="<?= e(base_url($ni['url'])) ?>" class="nav-link"><?= e($ni['label']) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="header-actions">
            <button class="icon-btn" aria-label="Search" data-search-toggle type="button">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg>
            </button>
            <a href="<?= base_url('account.php') ?>" class="icon-btn desktop-only" aria-label="Account">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/></svg>
            </a>
            <button class="icon-btn cart-btn" aria-label="Cart" data-cart-toggle type="button">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h14l-1.5 11a2 2 0 01-2 1.7H8.5a2 2 0 01-2-1.7L5 7z"/><path d="M9 7V5a3 3 0 016 0v2"/></svg>
                <?php if ($cartCount > 0): ?><span class="cart-badge"><?= (int)$cartCount ?></span><?php endif; ?>
            </button>
        </div>
    </div>
</header>

<!-- Mobile nav drawer -->
<div class="drawer" data-nav-drawer aria-hidden="true">
    <div class="drawer-overlay" data-drawer-close></div>
    <div class="drawer-panel drawer-left">
        <div class="drawer-head">
            <a href="<?= base_url('index.php') ?>" class="brand">
                <span class="brand-mark">M</span>
                <span class="brand-name"><?= e($siteName) ?></span>
            </a>
            <button class="icon-btn" data-drawer-close aria-label="Close" type="button">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="6" y1="18" x2="18" y2="6"/></svg>
            </button>
        </div>
        <nav class="drawer-body">
            <?php foreach ($navItems as $ni): ?>
            <a href="<?= e(base_url($ni['url'])) ?>" class="drawer-link" style="display:flex;align-items:center;padding:14px 16px;border-radius:10px;font-size:15px;font-weight:500;min-width:0"><?= e($ni['label']) ?></a>
            <?php endforeach; ?>
            <a href="<?= base_url('account.php') ?>" style="display:flex;align-items:center;padding:14px 16px;border-radius:10px;font-size:15px;font-weight:500">Account</a>
            <a href="<?= base_url('track-order.php') ?>" style="display:flex;align-items:center;padding:14px 16px;border-radius:10px;font-size:15px;font-weight:500">Track order</a>
        </nav>
    </div>
</div>

<!-- Search drawer -->
<div class="drawer" data-search-drawer aria-hidden="true">
    <div class="drawer-overlay" data-drawer-close></div>
    <div class="drawer-panel drawer-top">
        <div class="drawer-head">
            <form data-search-form style="display:flex;align-items:center;gap:12px;flex:1;min-width:0">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg>
                <input type="search" placeholder="Search products, categories, SKU…" data-search-input style="flex:1;border:none;outline:none;background:transparent;font-size:16px;color:var(--c-text);min-width:0" autocomplete="off">
            </form>
            <button class="icon-btn" data-drawer-close aria-label="Close" type="button">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="6" y1="18" x2="18" y2="6"/></svg>
            </button>
        </div>
        <div class="drawer-body" data-search-results></div>
    </div>
</div>

<!-- Cart drawer -->
<div class="drawer" data-cart-drawer aria-hidden="true">
    <div class="drawer-overlay" data-drawer-close></div>
    <div class="drawer-panel drawer-right">
        <div class="drawer-head">
            <div class="drawer-head-title">Your selection</div>
            <button class="icon-btn" data-drawer-close aria-label="Close" type="button">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="6" y1="18" x2="18" y2="6"/></svg>
            </button>
        </div>
        <div class="drawer-body" data-cart-body>
            <div class="empty-state"><p>Your bag is empty.</p></div>
        </div>
        <div class="drawer-foot" data-cart-foot>
            <a href="<?= base_url('checkout.php') ?>" class="btn btn-primary btn-block">Continue to checkout →</a>
            <a href="<?= base_url('cart.php') ?>" class="btn btn-ghost btn-block">View full bag</a>
        </div>
    </div>
</div>

<main class="site-main" id="siteMain">
