<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$q = trim($_GET['q'] ?? '');
$pageTitle = 'Search';
$results = [];

if (mb_strlen($q) >= 2) {
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare("SELECT p.*, c.name AS cat_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.status = 'active' AND (p.name LIKE ? OR p.sku LIKE ? OR p.tags LIKE ? OR p.short_description LIKE ?) ORDER BY p.id DESC LIMIT 60");
    $stmt->execute([$like, $like, $like, $like]);
    $results = $stmt->fetchAll();
}
?>
<div class="page-header"><div class="container" style="padding-top:24px">
    <div class="eyebrow">Search</div>
    <h1 class="section-title" style="font-size:30px;margin-top:8px"><?= $q ? 'Results for “' . e($q) . '”' : 'Find what you need' ?></h1>
</div></div>

<div class="search-page">
    <div class="container">
        <form data-search-form class="search-page-form">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search products, categories, SKU…">
            <button class="btn btn-primary" type="submit">Search</button>
        </form>

        <?php if ($q && !$results): ?>
        <div class="empty-state" style="padding:60px 0"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg><h2>No results</h2><p>We couldn't find any products matching “<?= e($q) ?>”. Try a different search.</p></div>
        <?php elseif ($results): ?>
        <div class="products-grid">
            <?php foreach ($results as $p):
                $img = $p['main_image'] ?: 'assets/images/defaults/product.svg';
                $pprice = (float)$p['price'];
                $psale = $p['sale_price'] !== null ? (float)$p['sale_price'] : null;
            ?>
            <article class="product-card">
                <a href="<?= base_url('product.php?id=' . (int)$p['id']) ?>" class="product-image">
                    <div class="product-badges">
                        <?php if ($psale && $pprice > 0): $disc = (int)round((1 - $psale / $pprice) * 100); ?><span class="badge badge-primary">-<?= $disc ?>%</span><?php endif; ?>
                    </div>
                    <img src="<?= e(APP_URL . '/' . $img) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                </a>
                <div class="product-body">
                    <?php if (!empty($p['cat_name'])): ?><div class="product-category"><?= e($p['cat_name']) ?></div><?php endif; ?>
                    <a href="<?= base_url('product.php?id=' . (int)$p['id']) ?>" class="product-name"><?= e($p['name']) ?></a>
                    <div class="product-price">
                        <?php if ($psale): ?>
                            <span class="price-sale"><?= e(format_money($psale)) ?></span>
                            <span class="price-old"><?= e(format_money($pprice)) ?></span>
                        <?php else: ?>
                            <span class="price-current"><?= e(format_money($pprice)) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-actions">
                        <button class="btn btn-primary" data-add-to-cart="<?= (int)$p['id'] ?>" type="button" <?= (int)$p['stock'] <= 0 ? 'disabled' : '' ?>><?= (int)$p['stock'] <= 0 ? 'Out of stock' : 'Add to cart' ?></button>
                        <a href="<?= base_url('product.php?id=' . (int)$p['id']) ?>" class="btn btn-ghost">View product</a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
