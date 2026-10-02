<?php
/**
 * Admin layout — emits <html> + sidebar + main shell
 */
function admin_header(string $title = 'Dashboard', string $active = ''): void {
    $siteName = setting('site_name', 'Meridian');
    ?>
    <!doctype html>
    <html lang="en">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($title) ?> · <?= e($siteName) ?> Admin</title>
    <link rel="stylesheet" href="<?= asset('css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/desktop.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/tablet.css') ?>" media="(min-width: 768px) and (max-width: 1023.98px)">
    <link rel="stylesheet" href="<?= asset('css/mobile.css') ?>" media="(max-width: 767.98px)">
    <style><?= theme_css_vars() ?></style>
    </head>
    <body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="admin-brand">
                <a href="<?= base_url('admin/dashboard.php') ?>" class="brand">
                    <span class="brand-mark">M</span>
                    <span class="brand-name"><?= e($siteName) ?></span>
                </a>
            </div>
            <nav class="admin-nav">
                <?php
                $items = [
                    ['dashboard.php',  'Dashboard',  'grid', null],
                    ['products.php',  'Products',   'box', null],
                    ['product-edit.php', 'Add product', 'plus', 'products.php'],
                    ['categories.php', 'Categories', 'tag', null],
                    ['orders.php',    'Orders',     'cart', null],
                    ['payments.php',  'Payments',   'wallet', null],
                    ['customers.php', 'Customers',  'user', null],
                    ['inventory.php', 'Inventory',  'layers', null],
                    ['coupons.php',   'Coupons',    'ticket', null],
                    ['shipping.php',  'Shipping',   'truck', null],
                    ['homepage.php',  'Homepage',   'home', null],
                    ['sections.php',  'Sections',   'layout', null],
                    ['theme.php',     'Theme',      'droplet', null],
                    ['navigation.php','Navigation', 'menu', null],
                    ['pages.php',     'Pages',      'file', null],
                    ['whatsapp.php',  'WhatsApp',   'phone', null],
                    ['telegram.php',  'Telegram',   'send', null],
                    ['messenger.php', 'Messenger',  'message', null],
                    ['notifications.php','Notifications','bell', null],
                    ['admins.php',    'Admins',     'shield', null],
                    ['settings.php',  'Settings',   'settings', null],
                    ['logs.php',      'Logs',       'list', null],
                ];
                foreach ($items as $it):
                    $href = $it[0]; $label = $it[1]; $icon = $it[2]; $parent = $it[3] ?? null;
                    $isActive = ($active === $href) || ($parent && $active === $parent);
                ?>
                <a href="<?= e(base_url('admin/' . $href)) ?>" class="admin-link <?= $isActive ? 'active' : '' ?>">
                    <?= admin_icon($icon) ?><span><?= e($label) ?></span>
                </a>
                <?php endforeach; ?>
            </nav>
            <div class="admin-side-foot">
                <a href="<?= base_url('index.php') ?>" class="admin-link"><?= admin_icon('external') ?><span>View store</span></a>
                <a href="<?= base_url('admin/logout.php') ?>" class="admin-link"><?= admin_icon('logout') ?><span>Log out</span></a>
            </div>
        </aside>
        <div class="admin-main">
            <header class="admin-topbar">
                <button class="icon-btn admin-menu-toggle" id="adminMenuToggle" type="button" aria-label="Menu">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><line x1="3" y1="7" x2="21" y2="7"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="17" x2="21" y2="17"/></svg>
                </button>
                <h1 class="admin-page-title"><?= e($title) ?></h1>
                <div class="admin-top-actions">
                    <span style="font-size:13px;color:var(--c-muted);display:none" class="desktop-only"><?= e(admin_user()['name'] ?? '') ?></span>
                </div>
            </header>
            <main class="admin-content">
    <?php
}

function admin_footer(): void {
    ?>
            </main>
        </div>
        <div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>
    </div>
    <script>window.MERIDIAN = { base: '<?= e(APP_URL) ?>', csrf: '<?= e(csrf_token()) ?>' };</script>
    <script src="<?= asset('js/main.js') ?>"></script>
    <script src="<?= asset('js/admin.js') ?>"></script>
    </body>
    </html>
    <?php
}

function admin_icon(string $name): string {
    $c = 'viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"';
    $icons = [
        'grid'     => '<svg ' . $c . '><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
        'box'      => '<svg ' . $c . '><path d="M3 7l9-4 9 4v10l-9 4-9-4V7z"/><path d="M3 7l9 4 9-4"/><line x1="12" y1="11" x2="12" y2="21"/></svg>',
        'plus'     => '<svg ' . $c . '><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
        'tag'      => '<svg ' . $c . '><path d="M3 11V3h8l10 10-8 8L3 11z"/><circle cx="7" cy="7" r="1.5" fill="currentColor"/></svg>',
        'cart'     => '<svg ' . $c . '><path d="M5 7h14l-1.5 11a2 2 0 01-2 1.7H8.5a2 2 0 01-2-1.7L5 7z"/><path d="M9 7V5a3 3 0 016 0v2"/></svg>',
        'wallet'   => '<svg ' . $c . '><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M16 13h3"/></svg>',
        'user'     => '<svg ' . $c . '><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/></svg>',
        'layers'   => '<svg ' . $c . '><path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/></svg>',
        'ticket'   => '<svg ' . $c . '><path d="M3 9a2 2 0 002-2V5h14v2a2 2 0 002 2v6a2 2 0 00-2 2v2H5v-2a2 2 0 00-2-2V9z"/></svg>',
        'truck'    => '<svg ' . $c . '><rect x="2" y="6" width="14" height="12" rx="1"/><path d="M16 9h4l2 3v6h-6"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg>',
        'home'     => '<svg ' . $c . '><path d="M3 11l9-8 9 8v9a2 2 0 01-2 2h-4v-7h-6v7H5a2 2 0 01-2-2v-9z"/></svg>',
        'layout'   => '<svg ' . $c . '><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="9" x2="9" y2="21"/></svg>',
        'droplet'  => '<svg ' . $c . '><path d="M12 3c-3 5-7 8-7 13a7 7 0 0014 0c0-5-4-8-7-13z"/></svg>',
        'menu'     => '<svg ' . $c . '><line x1="3" y1="7" x2="21" y2="7"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="17" x2="21" y2="17"/></svg>',
        'file'     => '<svg ' . $c . '><path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"/><polyline points="14 3 14 9 20 9"/></svg>',
        'phone'    => '<svg ' . $c . '><path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1.9.3 1.8.6 2.6a2 2 0 01-.5 2.1L8 9.7a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.5c.8.3 1.7.5 2.6.6a2 2 0 011.7 2z"/></svg>',
        'send'     => '<svg ' . $c . '><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
        'message'  => '<svg ' . $c . '><path d="M21 12a8 8 0 11-3-6.2L21 4l-1 4-1.8.6"/></svg>',
        'bell'     => '<svg ' . $c . '><path d="M18 16v-5a6 6 0 10-12 0v5l-2 3h16l-2-3z"/><path d="M10 21a2 2 0 004 0"/></svg>',
        'shield'   => '<svg ' . $c . '><path d="M12 3l8 4v6c0 4-3 7-8 8-5-1-8-4-8-8V7l8-4z"/></svg>',
        'settings' => '<svg ' . $c . '><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.5-1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H9a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9a1.7 1.7 0 001.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z"/></svg>',
        'list'     => '<svg ' . $c . '><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg>',
        'external' => '<svg ' . $c . '><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
        'logout'   => '<svg ' . $c . '><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
    ];
    return $icons[$name] ?? '';
}
