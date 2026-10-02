<?php
if (!defined('APP_NAME')) { http_response_code(403); exit; }

function render_product_card(array $p): string {
    $img = $p['main_image'] ?: 'assets/images/defaults/product.svg';
    $imgUrl = (str_starts_with($img, 'http') || str_starts_with($img, '/')) ? $img : (APP_URL . '/' . $img);
    $price = (float)$p['price'];
    $sale = $p['sale_price'] !== null ? (float)$p['sale_price'] : null;
    $discount = ($sale && $price > 0) ? (int)round((1 - $sale / $price) * 100) : 0;
    $stock = (int)$p['stock'];
    $pid = (int)$p['id'];
    $url = base_url('product.php?id=' . $pid);
    $rating = (float)($p['rating_avg'] ?? 0);
    $rcount = (int)($p['rating_count'] ?? 0);
    $cat = $p['cat_name'] ?? '';

    ob_start();
    ?>
    <article class="product-card" data-product-id="<?= $pid ?>">
        <a href="<?= e($url) ?>" class="product-image" aria-label="<?= e($p['name']) ?>">
            <div class="product-badges">
                <?php if ($discount > 0): ?><span class="badge badge-primary">-<?= $discount ?>%</span><?php endif; ?>
                <?php if (!empty($p['is_new'])): ?><span class="badge" style="background:#fff;color:var(--c-text);border:1px solid var(--c-border)">New</span><?php endif; ?>
            </div>
            <img src="<?= e($imgUrl) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
            <button class="product-wishlist" data-wishlist="<?= $pid ?>" type="button" aria-label="Add to wishlist">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 6.6a5.5 5.5 0 00-7.8 0L12 7.6l-1-1a5.5 5.5 0 10-7.8 7.8l1 1L12 23l7.8-7.8 1-1a5.5 5.5 0 000-7.6z"/></svg>
            </button>
        </a>
        <div class="product-body">
            <?php if ($cat): ?><div class="product-category"><?= e($cat) ?></div><?php endif; ?>
            <a href="<?= e($url) ?>" class="product-name"><?= e($p['name']) ?></a>
            <?php if ($rcount > 0): ?>
            <div class="product-rating">
                <span class="product-rating-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?><svg viewBox="0 0 24 24" width="11" height="11" fill="currentColor"><polygon points="12 2 15.1 8.5 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 8.9 8.5"/></svg><?php endfor; ?>
                </span>
                <span><?= number_format($rating, 1) ?> (<?= $rcount ?>)</span>
            </div>
            <?php endif; ?>
            <div class="product-price">
                <?php if ($sale): ?>
                    <span class="price-sale"><?= e(format_money($sale)) ?></span>
                    <span class="price-old"><?= e(format_money($price)) ?></span>
                    <?php if ($discount): ?><span class="price-discount">-<?= $discount ?>%</span><?php endif; ?>
                <?php else: ?>
                    <span class="price-current"><?= e(format_money($price)) ?></span>
                <?php endif; ?>
            </div>
            <div class="product-actions">
                <button class="btn btn-primary" data-add-to-cart="<?= $pid ?>" type="button" <?= $stock <= 0 ? 'disabled' : '' ?>>
                    <?= $stock <= 0 ? 'Out of stock' : 'Add to cart' ?>
                </button>
                <a href="<?= e($url) ?>" class="btn btn-ghost">View product</a>
            </div>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

function section_render(string $key, PDO $pdo): void {
    switch ($key) {
        case 'hero': render_hero($pdo); break;
        case 'categories': render_categories($pdo); break;
        case 'featured': render_grid($pdo, 'featured', t('text_section_featured', 'Featured'), t('text_section_featured_sub', 'Hand-picked essentials.')); break;
        case 'new_arrivals': render_grid($pdo, 'new', t('text_section_new', 'New arrivals'), t('text_section_new_sub', 'Just landed in the studio.')); break;
        case 'bestsellers': render_grid($pdo, 'bestseller', t('text_section_best', 'Best sellers'), t('text_section_best_sub', 'Loved by the community.')); break;
        case 'intention': render_intention($pdo); break;
        case 'promo': render_promo($pdo); break;
        case 'testimonials': render_testimonials($pdo); break;
        case 'newsletter': render_newsletter(); break;
    }
}

function render_hero(PDO $pdo): void {
    $eyebrow = t('text_hero_eyebrow', 'The 2026 Collection');
    $title = t('text_hero_title', 'Less noise. More wonder.');
    $sub = t('text_hero_subtitle', 'Premium objects selected to make everyday moments feel extraordinary.');
    $btn1 = t('text_hero_button1', 'Explore collection');
    $btn2 = t('text_hero_button2', 'Discover new arrivals');
    ?>
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-copy">
                <div class="eyebrow"><?= e($eyebrow) ?></div>
                <h1 class="hero-title"><?= e($title) ?></h1>
                <p class="hero-sub"><?= e($sub) ?></p>
                <div class="hero-cta">
                    <a href="<?= base_url('category.php?id=1') ?>" class="btn btn-primary btn-lg"><?= e($btn1) ?> →</a>
                    <a href="<?= base_url('category.php?id=2') ?>" class="btn btn-ghost btn-lg"><?= e($btn2) ?></a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-card">
                    <svg viewBox="0 0 400 500" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" style="width:100%;height:100%">
                        <defs><linearGradient id="hg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#fafafa"/><stop offset="100%" stop-color="#fff"/></linearGradient></defs>
                        <rect width="400" height="500" fill="url(#hg)"/>
                        <circle cx="290" cy="170" r="130" fill="#111" opacity="0.04"/>
                        <rect x="80" y="220" width="240" height="200" rx="20" fill="#111" opacity="0.05"/>
                        <text x="200" y="280" text-anchor="middle" font-family="system-ui" font-size="56" font-weight="600" fill="#111" opacity="0.85">2026</text>
                        <text x="200" y="320" text-anchor="middle" font-family="system-ui" font-size="14" font-weight="500" letter-spacing="2" fill="#666">COLLECTION</text>
                    </svg>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function render_categories(PDO $pdo): void {
    $cats = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY position ASC LIMIT 8")->fetchAll();
    if (!$cats) return;
    ?>
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <div class="eyebrow"><?= e(t('text_section_categories', 'Shop by category')) ?></div>
                    <h2 class="section-title"><?= e(t('text_section_categories_sub', 'Find your focus.')) ?></h2>
                </div>
                <a href="<?= base_url('categories.php') ?>" class="section-link">View all →</a>
            </div>
            <div class="category-grid">
                <?php foreach ($cats as $c): ?>
                <a href="<?= base_url('category.php?id=' . (int)$c['id']) ?>" class="category-card">
                    <div class="category-image">
                        <?php if (!empty($c['image']) && file_exists(APP_ROOT . '/' . $c['image'])): ?>
                            <img src="<?= e(APP_URL . '/' . $c['image']) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="category-placeholder">
                                <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.2"><circle cx="12" cy="12" r="10"/></svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="category-info">
                        <h3 class="category-name"><?= e($c['name']) ?></h3>
                        <span class="category-arrow">→</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}

function render_grid(PDO $pdo, string $type, string $title, string $subtitle): void {
    $where = "p.status = 'active'";
    switch ($type) {
        case 'featured': $where .= " AND p.is_featured = 1"; break;
        case 'new': $where .= " AND p.is_new = 1"; break;
        case 'bestseller': $where .= " AND p.is_bestseller = 1"; break;
    }
    $stmt = $pdo->prepare("SELECT p.*, c.name AS cat_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $where ORDER BY p.id DESC LIMIT 12");
    $stmt->execute();
    $products = $stmt->fetchAll();
    if (!$products) return;
    $sectionId = 'sec-' . $type;
    ?>
    <section class="section" id="<?= e($sectionId) ?>">
        <div class="container">
            <div class="section-head">
                <div>
                    <div class="eyebrow"><?= e($title) ?></div>
                    <h2 class="section-title"><?= e($subtitle) ?></h2>
                </div>
                <a href="<?= base_url('category.php') ?>" class="section-link">View all →</a>
            </div>
            <div class="product-carousel" data-carousel>
                <button class="carousel-btn prev" data-carousel-prev type="button" aria-label="Previous" disabled>
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 6 9 12 15 18"/></svg>
                </button>
                <div class="carousel-track" data-carousel-track>
                    <?php foreach ($products as $p) echo render_product_card($p); ?>
                </div>
                <button class="carousel-btn next" data-carousel-next type="button" aria-label="Next">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>
                </button>
            </div>
        </div>
    </section>
    <?php
}

function render_intention(PDO $pdo): void {
    $items = [
        ['For focus', 'For the desk, for deep work.'],
        ['For the home', 'Considered objects for considered spaces.'],
        ['For travel', 'Built to last, sized to carry.'],
        ['For everyday', 'The small things, finished well.'],
    ];
    ?>
    <section class="section section-soft">
        <div class="container">
            <div class="section-head">
                <div>
                    <div class="eyebrow"><?= e(t('text_section_intention', 'Shop by intention')) ?></div>
                    <h2 class="section-title"><?= e(t('text_section_intention_sub', 'A guided way to discover.')) ?></h2>
                </div>
            </div>
            <div class="intention-grid">
                <?php foreach ($items as [$label, $sub]): ?>
                <a href="<?= base_url('category.php') ?>" class="intention-card">
                    <div class="intention-illu">
                        <svg viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="0.8">
                            <circle cx="50" cy="50" r="40"/><circle cx="50" cy="50" r="25"/><circle cx="50" cy="50" r="10"/>
                        </svg>
                    </div>
                    <div style="min-width:0">
                        <div class="intention-label"><?= e($label) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}

function render_promo(PDO $pdo): void {
    ?>
    <section class="section">
        <div class="container">
            <div class="promo">
                <div class="promo-content">
                    <div class="eyebrow"><?= e(t('text_section_promo', 'Limited edition')) ?></div>
                    <h2 class="promo-title"><?= e(t('text_section_promo_sub', 'Released in small batches.')) ?></h2>
                    <p>Each release is a small batch, finished by hand and shipped directly from the studio.</p>
                    <a href="<?= base_url('category.php?id=1') ?>" class="btn btn-primary btn-lg">Discover →</a>
                </div>
                <div class="promo-visual">
                    <div class="promo-badge">— 2026 —</div>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function render_testimonials(PDO $pdo): void {
    $items = [
        ['Anika R.', 'Aria Wireless Headphones', 'The detail on these is something else. I use them every day.'],
        ['Maruf H.', 'Halo Smart Lamp', 'Quietly became the centre of the room. Calming, warm, and well built.'],
        ['Sara K.', 'Vessel Ceramic Mug', 'The finish is so satisfying in the hand. I bought two more.'],
    ];
    ?>
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <div class="eyebrow"><?= e(t('text_section_test', 'What people say')) ?></div>
                    <h2 class="section-title"><?= e(t('text_section_test_sub', 'Real words from real owners.')) ?></h2>
                </div>
            </div>
            <div class="testimonial-grid">
                <?php foreach ($items as [$name, $product, $quote]): ?>
                <figure class="testimonial">
                    <svg class="quote-mark" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M7 7h4v4H8c0 2 1 3 3 3v2c-3 0-5-2-5-5V7zm9 0h4v4h-3c0 2 1 3 3 3v2c-3 0-5-2-5-5V7z"/></svg>
                    <blockquote><?= e($quote) ?></blockquote>
                    <figcaption>
                        <span class="t-name"><?= e($name) ?></span>
                        <span class="t-product">on <?= e($product) ?></span>
                    </figcaption>
                </figure>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}

function render_newsletter(): void {
    ?>
    <section class="section">
        <div class="container">
            <div class="newsletter">
                <div class="newsletter-info">
                    <div class="eyebrow"><?= e(t('text_section_news', 'Stay in the loop')) ?></div>
                    <h2 class="section-title" style="margin-top:8px"><?= e(t('text_section_news_sub', 'Quiet updates. No noise.')) ?></h2>
                </div>
                <form class="newsletter-form" data-newsletter>
                    <input type="email" placeholder="you@example.com" required>
                    <button class="btn btn-primary" type="submit">Subscribe</button>
                </form>
            </div>
        </div>
    </section>
    <?php
}

$pdo = db();
$sections = $pdo ? $pdo->query("SELECT * FROM homepage_sections WHERE status = 'enabled' ORDER BY position ASC")->fetchAll() : [];
foreach ($sections as $sec) section_render($sec['section_key'], $pdo);
