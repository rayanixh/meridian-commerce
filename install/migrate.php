<?php
/**
 * Migration runner + seed (idempotent, MariaDB-compatible).
 * Never drops existing tables or data.
 */

function run_migrations(?PDO $pdo = null, bool $force = false): array {
    $pdo = $pdo ?? db();
    if (!$pdo) return ['ok' => false, 'error' => 'No database connection'];

    $results = [];
    $migrations = require __DIR__ . '/migrations.php';

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(100) NOT NULL UNIQUE,
            batch INT NOT NULL DEFAULT 1,
            executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Cannot create migrations table: ' . $e->getMessage()];
    }

    $done = [];
    foreach ($pdo->query("SELECT migration FROM migrations") as $r) {
        $done[$r['migration']] = true;
    }

    foreach ($migrations as $name => $sql) {
        if (!empty($done[$name]) && !$force) {
            $results[] = ['name' => $name, 'status' => 'skipped'];
            continue;
        }
        try {
            $pdo->exec($sql);
            // Use INSERT IGNORE so re-running is safe
            $stmt = $pdo->prepare("INSERT IGNORE INTO migrations (migration, batch) VALUES (?, 1)");
            $stmt->execute([$name]);
            $results[] = ['name' => $name, 'status' => 'ok'];
        } catch (Throwable $e) {
            $results[] = ['name' => $name, 'status' => 'failed', 'error' => $e->getMessage()];
            log_error('migration', "$name: " . $e->getMessage());
            return [
                'ok'      => false,
                'error'   => "Migration $name failed: " . $e->getMessage(),
                'results' => $results,
            ];
        }
    }

    // Seed
    $seed = run_seed($pdo);
    if (!$seed['ok']) {
        return ['ok' => false, 'error' => 'Seed failed: ' . $seed['error'], 'results' => $results];
    }

    return ['ok' => true, 'results' => $results];
}

function run_seed(PDO $pdo): array {
    try {
        // Default admin
        $row = $pdo->query("SELECT COUNT(*) AS c FROM admins")->fetch();
        if ((int)$row['c'] === 0) {
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, 'superadmin')");
            $stmt->execute(['Administrator', 'admin@meridian.local', $hash]);
        }

        // Settings (idempotent via INSERT IGNORE)
        $defaults = [
            'site_name' => 'Meridian', 'site_tagline' => 'Objects for the considered life.',
            'site_description' => 'A premium collection of considered objects for everyday life.',
            'currency' => 'BDT', 'currency_symbol' => '৳',
            'footer_text' => '© ' . date('Y') . ' Meridian. Crafted with care.',
            'announcement_text' => 'Free shipping on orders over ৳3,000 — 30 day returns.',
            'shipping_inside' => '60', 'shipping_outside' => '120', 'shipping_free_threshold' => '3000',
            'whatsapp_enabled' => '0', 'whatsapp_api_url' => '', 'whatsapp_api_token' => '', 'whatsapp_recipient' => '',
            'telegram_enabled' => '0', 'telegram_bot_token' => '', 'telegram_chat_id' => '',
            'messenger_enabled' => '0', 'messenger_url' => '',
            'theme_bg' => '#FFFFFF', 'theme_surface' => '#FAFAFA', 'theme_card' => '#FFFFFF',
            'theme_text' => '#111111', 'theme_muted' => '#666666', 'theme_border' => '#E5E5E5',
            'theme_button' => '#111111', 'theme_button_text' => '#FFFFFF', 'theme_accent' => '#111111',
            'theme_header' => '#FFFFFF', 'theme_footer_bg' => '#0A0A0A', 'theme_footer_text' => '#CCCCCC',
            'theme_border_radius' => '12', 'theme_button_radius' => '10', 'theme_blur' => '14',
            'theme_glass_opacity' => '0.85', 'theme_animation' => '1',
            'logo_main' => '', 'logo_mobile' => '', 'logo_footer' => '', 'favicon' => '',
            // 'installed' is intentionally NOT seeded — it is set by the installer's step 6/7 'finish' action.
            'text_hero_eyebrow' => 'The 2026 Collection',
            'text_hero_title' => 'Less noise. More wonder.',
            'text_hero_subtitle' => 'Premium objects selected to make everyday moments feel extraordinary.',
            'text_hero_button1' => 'Explore collection',
            'text_hero_button2' => 'Discover new arrivals',
            'text_section_featured' => 'Featured',
            'text_section_featured_sub' => 'Hand-picked essentials.',
            'text_section_new' => 'New arrivals',
            'text_section_new_sub' => 'Just landed in the studio.',
            'text_section_best' => 'Best sellers',
            'text_section_best_sub' => 'Loved by the community.',
            'text_section_categories' => 'Shop by category',
            'text_section_categories_sub' => 'Find your focus.',
            'text_section_intention' => 'Shop by intention',
            'text_section_intention_sub' => 'A guided way to discover.',
            'text_section_promo' => 'Limited edition',
            'text_section_promo_sub' => 'Released in small batches.',
            'text_section_test' => 'What people say',
            'text_section_test_sub' => 'Real words from real owners.',
            'text_section_news' => 'Stay in the loop',
            'text_section_news_sub' => 'Quiet updates. No noise.',
        ];
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, 'general')");
        foreach ($defaults as $k => $v) $stmt->execute([$k, $v]);

        // Payment methods (skip if any exist)
        $count = (int)$pdo->query("SELECT COUNT(*) AS c FROM payment_methods")->fetch()['c'];
        if ($count === 0) {
            $methods = [
                ['bkash', 'bKash', 'Pay securely with bKash. Send money to the merchant number shown at checkout, then enter your Transaction ID below.', 0],
                ['nagad', 'Nagad', 'Pay securely with Nagad. Send money to the merchant number shown at checkout, then enter your Transaction ID below.', 1],
                ['rocket', 'Rocket', 'Pay with Rocket mobile banking. Send money to the merchant number shown at checkout, then enter your Transaction ID below.', 2],
                ['cod', 'Cash on Delivery', 'Pay when your order arrives. No payment required now.', 3],
            ];
            $ins = $pdo->prepare("INSERT INTO payment_methods (code, name, instructions, position, status) VALUES (?, ?, ?, ?, 'active')");
            foreach ($methods as $m) $ins->execute($m);
        }

        // Categories
        $count = (int)$pdo->query("SELECT COUNT(*) AS c FROM categories")->fetch()['c'];
        if ($count === 0) {
            $cats = [
                ['Everyday Tech', 'Premium tech for daily rituals.', 1],
                ['Home & Living', 'Considered objects for the home.', 2],
                ['Audio', 'Sound, refined.', 3],
                ['Accessories', 'Small things, finished well.', 4],
                ['Lifestyle', 'For the everyday.', 5],
            ];
            $ins = $pdo->prepare("INSERT INTO categories (name, slug, description, position, status) VALUES (?, ?, ?, ?, 'active')");
            foreach ($cats as $c) $ins->execute([$c[0], slugify($c[0]), $c[1], $c[2]]);
        }

        // Navigation
        $count = (int)$pdo->query("SELECT COUNT(*) AS c FROM navigation_items")->fetch()['c'];
        if ($count === 0) {
            $items = [
                ['desktop', 'Shop',     'category.php?id=1', 1, 'bag'],
                ['desktop', 'New',      'category.php?id=1&sort=newest', 2, 'sparkle'],
                ['desktop', 'Best',     'category.php?id=1', 3, 'star'],
                ['desktop', 'Story',    'page.php?slug=about', 4, 'book'],
                ['footer',  'Customer Care', 'page.php?slug=support', 1, ''],
                ['footer',  'Shipping', 'page.php?slug=shipping', 2, ''],
                ['footer',  'Returns',  'page.php?slug=returns', 3, ''],
                ['footer',  'Contact',  'page.php?slug=contact', 4, ''],
                ['bottom',  'Home',     'index.php', 1, 'home'],
                ['bottom',  'Shop',     'categories.php', 2, 'grid'],
                ['bottom',  'Search',   'search.php', 3, 'search'],
                ['bottom',  'Cart',     'cart.php', 4, 'bag'],
                ['bottom',  'Account',  'account.php', 5, 'user'],
            ];
            $ins = $pdo->prepare("INSERT INTO navigation_items (location, label, url, position, icon) VALUES (?, ?, ?, ?, ?)");
            foreach ($items as $i) $ins->execute($i);
        }

        // Products
        $count = (int)$pdo->query("SELECT COUNT(*) AS c FROM products")->fetch()['c'];
        if ($count === 0) {
            $catRows = $pdo->query("SELECT id, slug FROM categories ORDER BY id ASC")->fetchAll();
            $catMap = [];
            foreach ($catRows as $r) $catMap[$r['slug']] = (int)$r['id'];

            $products = [
                ['Aria Wireless Headphones', 'Aria',     $catMap['audio'] ?? null,         'audio-1.svg',  8900, 7900, 'Over-ear wireless headphones with adaptive noise reduction and 40h battery.', 1, 0, 1],
                ['Halo Smart Lamp',          'Halo',     $catMap['home-living'] ?? null,   'lamp-1.svg',   4500, 3990, 'A dimmable ambient lamp with a soft warm glow and brushed brass base.',         1, 1, 1],
                ['Vessel Ceramic Mug',       'Vessel',   $catMap['home-living'] ?? null,   'mug-1.svg',    1200, 990,  'Hand-thrown ceramic mug, finished in matte stoneware.',                        0, 1, 0],
                ['Stride Travel Backpack',   'Stride',   $catMap['everyday-tech'] ?? null, 'bag-1.svg',    6800, 5900, 'A 22L commuter backpack with a dedicated laptop sleeve and weather shell.',  1, 0, 1],
                ['Field Notebook Set',       'Field',    $catMap['lifestyle'] ?? null,     'book-1.svg',   900,  null, 'Three pocket notebooks with dotted paper and cotton binding.',               0, 0, 1],
                ['Loop Leather Wallet',      'Loop',     $catMap['accessories'] ?? null,   'wallet-1.svg', 2200, 1850, 'Slim bifold wallet, vegetable-tanned leather.',                                0, 1, 0],
                ['Onyx Desk Speaker',        'Onyx',     $catMap['audio'] ?? null,         'speaker-1.svg', 12500, null, 'Compact studio-grade desktop speaker with hand-finished enclosure.',       1, 0, 1],
                ['Linen Throw Blanket',      'Linen',    $catMap['home-living'] ?? null,   'blanket-1.svg', 3400, null, 'Stonewashed linen throw, woven on traditional looms.',                       0, 1, 1],
            ];
            $ins = $pdo->prepare("INSERT INTO products (name, slug, sku, category_id, short_description, description, price, sale_price, stock, main_image, is_featured, is_bestseller, is_new, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            foreach ($products as $p) {
                [$name, $slug, $catId, $img, $price, $sale, $short, $feat, $best, $new] = $p;
                $sku = 'MC-' . strtoupper(substr(md5($slug . microtime(true)), 0, 6));
                $ins->execute([$name, $slug, $sku, $catId, $short, $short, $price, $sale, 50, 'assets/images/defaults/' . $img, $feat, $best, $new]);
            }
        }

        // Homepage sections
        $count = (int)$pdo->query("SELECT COUNT(*) AS c FROM homepage_sections")->fetch()['c'];
        if ($count === 0) {
            $secs = [
                ['hero', 1], ['categories', 2], ['featured', 3], ['intention', 4],
                ['new_arrivals', 5], ['bestsellers', 6], ['promo', 7], ['testimonials', 8], ['newsletter', 9],
            ];
            $ins = $pdo->prepare("INSERT INTO homepage_sections (section_key, position, status) VALUES (?, ?, 'enabled')");
            foreach ($secs as $s) $ins->execute($s);
        }

        return ['ok' => true];
    } catch (Throwable $e) {
        log_error('seed', $e->getMessage());
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

function check_required_tables(): array {
    $pdo = db();
    if (!$pdo) return ['ok' => false, 'missing' => ['*']];
    $required = ['users','admins','categories','products','orders','order_items',
                 'payment_methods','settings','coupons','shipping_zones','pages',
                 'navigation_items','homepage_sections','product_variants',
                 'notifications','login_attempts','activity_logs','payment_logs',
                 'wishlist','migrations'];
    $existing = [];
    foreach ($pdo->query("SHOW TABLES") as $r) {
        $name = $r[0] ?? (is_array($r) ? reset($r) : $r);
        if ($name) $existing[] = $name;
    }
    $missing = array_diff($required, $existing);
    return ['ok' => empty($missing), 'existing' => $existing, 'missing' => array_values($missing)];
}
