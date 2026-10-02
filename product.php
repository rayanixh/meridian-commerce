<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$product = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
} elseif (!empty($_GET['slug'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE slug = ? AND status = 'active'");
    $stmt->execute([$_GET['slug']]);
    $product = $stmt->fetch();
    if ($product) $id = (int)$product['id'];
}
if (!$product) {
    echo '<div class="container" style="padding:80px 0 60px;text-align:center;min-width:0">';
    echo '<div style="width:64px;height:64px;border-radius:50%;background:var(--c-surface);display:inline-flex;align-items:center;justify-content:center;margin-bottom:20px"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--c-muted)"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg></div>';
    echo '<h1 style="font-size:24px;margin-bottom:8px">Product not found</h1>';
    echo '<p style="color:var(--c-muted);margin-bottom:24px">The product you’re looking for is no longer available.</p>';
    echo '<a href="' . base_url('index.php') . '" class="btn btn-primary">Back to store</a>';
    echo '</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

try { $pdo->prepare("UPDATE products SET view_count = view_count + 1 WHERE id = ?")->execute([$id]); } catch (Throwable $e) {}

$varStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY name, value");
$varStmt->execute([$id]);
$variantGroups = [];
foreach ($varStmt as $v) $variantGroups[$v['name']][] = $v['value'];

$gallery = [];
if (!empty($product['main_image'])) $gallery[] = $product['main_image'];
if (!empty($product['gallery'])) {
    $g = json_decode($product['gallery'], true);
    if (is_array($g)) $gallery = array_merge($gallery, $g);
    else { $g = explode(',', $product['gallery']); $gallery = array_merge($gallery, array_filter($g)); }
}
if (empty($gallery)) $gallery[] = 'assets/images/defaults/product.svg';
$gallery = array_values(array_unique($gallery));

$related = [];
try {
    $r = $pdo->prepare("SELECT * FROM products WHERE status = 'active' AND id != ? AND category_id = ? ORDER BY id DESC LIMIT 4");
    $r->execute([$id, $product['category_id'] ?? 0]);
    $related = $r->fetchAll();
} catch (Throwable $e) {}

$price = (float)$product['price'];
$sale = $product['sale_price'] !== null ? (float)$product['sale_price'] : null;
$discount = ($sale && $price > 0) ? (int)round((1 - $sale / $price) * 100) : 0;
$stock = (int)$product['stock'];
$threshold = (int)$product['low_stock_threshold'];
$stockClass = $stock <= 0 ? 'danger' : ($stock <= $threshold ? 'warning' : '');
$stockText = $stock <= 0 ? 'Out of stock' : ($stock <= $threshold ? 'Only ' . $stock . ' left' : 'In stock');

$catName = '';
if ($product['category_id']) {
    $c = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $c->execute([$product['category_id']]);
    $catName = $c->fetchColumn() ?: '';
}

$pageTitle = $product['name'];
$pageJs = ['js/product.js'];
?>

<div class="product-page">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= base_url('index.php') ?>">Home</a>
            <span class="breadcrumb-sep">/</span>
            <a href="<?= base_url('category.php') ?>">Shop</a>
            <?php if ($catName): ?><span class="breadcrumb-sep">/</span><a href="<?= base_url('category.php?id=' . (int)$product['category_id']) ?>"><?= e($catName) ?></a><?php endif; ?>
            <span class="breadcrumb-sep">/</span>
            <span class="breadcrumb-current"><?= e($product['name']) ?></span>
        </nav>

        <div class="product-detail">
            <div class="product-gallery">
                <div class="gallery-main" data-gallery-main>
                    <img src="<?= e(APP_URL . '/' . $gallery[0]) ?>" alt="<?= e($product['name']) ?>">
                </div>
                <?php if (count($gallery) > 1): ?>
                <div class="gallery-thumbs">
                    <?php foreach ($gallery as $i => $g): ?>
                    <button class="<?= $i === 0 ? 'active' : '' ?>" data-thumb="<?= e(APP_URL . '/' . $g) ?>" type="button" aria-label="Image <?= $i+1 ?>">
                        <img src="<?= e(APP_URL . '/' . $g) ?>" alt="">
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="product-info">
                <?php if (!empty($product['sku'])): ?>
                <div class="product-meta">
                    <span>SKU: <?= e($product['sku']) ?></span>
                </div>
                <?php endif; ?>
                <h1><?= e($product['name']) ?></h1>

                <div class="product-price">
                    <?php if ($sale): ?>
                        <span class="price-sale"><?= e(format_money($sale)) ?></span>
                        <span class="price-old"><?= e(format_money($price)) ?></span>
                        <?php if ($discount): ?><span class="price-discount">-<?= $discount ?>%</span><?php endif; ?>
                    <?php else: ?>
                        <span class="price-current"><?= e(format_money($price)) ?></span>
                    <?php endif; ?>
                </div>

                <span class="stock-indicator <?= e($stockClass) ?>"><span class="dot"></span><?= e($stockText) ?></span>

                <?php if (!empty($product['short_description'])): ?>
                <p class="product-desc"><?= e($product['short_description']) ?></p>
                <?php endif; ?>

                <?php foreach ($variantGroups as $gname => $gvalues): ?>
                <div class="variant-group">
                    <span class="variant-label"><?= e($gname) ?></span>
                    <div class="variant-options" data-variant-group="<?= e($gname) ?>">
                        <?php foreach ($gvalues as $i => $v): ?>
                        <button class="variant-option <?= $i === 0 ? 'active' : '' ?>" data-variant-value="<?= e($v) ?>" type="button"><?= e($v) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="qty-cart">
                    <div class="qty-stepper">
                        <button data-qty-dec type="button" aria-label="Decrease">−</button>
                        <input type="text" value="1" data-qty-input>
                        <button data-qty-inc type="button" aria-label="Increase">+</button>
                    </div>
                    <button class="btn btn-primary" data-add-to-cart="<?= (int)$product['id'] ?>" data-variant="{}" type="button" <?= $stock <= 0 ? 'disabled' : '' ?>>
                        <?= $stock <= 0 ? 'Out of stock' : 'Add to cart' ?>
                    </button>
                    <button class="btn btn-ghost" data-buy-now type="button" <?= $stock <= 0 ? 'disabled' : '' ?>>Buy now</button>
                </div>

                <div class="product-features">
                    <div class="feature">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="14" height="12" rx="1"/><path d="M16 9h4l2 3v6h-6"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg>
                        <span>Free shipping over <?= e(format_money((float)setting('shipping_free_threshold', 0))) ?></span>
                    </div>
                    <div class="feature">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7l9-4 9 4v6c0 5-3.5 8-9 9-5.5-1-9-4-9-9V7z"/></svg>
                        <span>30-day returns</span>
                    </div>
                    <div class="feature">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>2-4 day delivery</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="product-tabs">
            <div class="tabs-nav">
                <a class="tab-link active" data-tab="description">Description</a>
                <a class="tab-link" data-tab="specs">Specifications</a>
                <a class="tab-link" data-tab="shipping">Shipping</a>
            </div>
            <div class="tab-pane active" data-tab-pane="description">
                <p><?= nl2br(e($product['description'] ?: $product['short_description'] ?: '')) ?></p>
            </div>
            <div class="tab-pane" data-tab-pane="specs">
                <table>
                    <tr><th>SKU</th><td><?= e($product['sku']) ?></td></tr>
                    <?php if ($product['brand']): ?><tr><th>Brand</th><td><?= e($product['brand']) ?></td></tr><?php endif; ?>
                    <?php if ($product['weight']): ?><tr><th>Weight</th><td><?= e($product['weight']) ?> kg</td></tr><?php endif; ?>
                    <?php if ($catName): ?><tr><th>Category</th><td><?= e($catName) ?></td></tr><?php endif; ?>
                </table>
            </div>
            <div class="tab-pane" data-tab-pane="shipping">
                <p>Inside Dhaka: <?= e(format_money((float)setting('shipping_inside', 60))) ?>. Outside Dhaka: <?= e(format_money((float)setting('shipping_outside', 120))) ?>. Free shipping on orders over <?= e(format_money((float)setting('shipping_free_threshold', 0))) ?>.</p>
            </div>
        </div>

        <?php if ($related): ?>
        <section class="section" style="padding:60px 0 0">
            <div class="section-head">
                <div>
                    <div class="eyebrow">Related</div>
                    <h2 class="section-title">You might also like</h2>
                </div>
            </div>
            <div class="products-grid">
                <?php foreach ($related as $rp):
                    $img = $rp['main_image'] ?: 'assets/images/defaults/product.svg';
                    $rprice = (float)$rp['price'];
                    $rsale = $rp['sale_price'] !== null ? (float)$rp['sale_price'] : null;
                ?>
                <article class="product-card">
                    <a href="<?= base_url('product.php?id=' . (int)$rp['id']) ?>" class="product-image">
                        <img src="<?= e(APP_URL . '/' . $img) ?>" alt="<?= e($rp['name']) ?>" loading="lazy">
                    </a>
                    <div class="product-body">
                        <a href="<?= base_url('product.php?id=' . (int)$rp['id']) ?>" class="product-name"><?= e($rp['name']) ?></a>
                        <div class="product-price">
                            <?php if ($rsale): ?>
                                <span class="price-sale"><?= e(format_money($rsale)) ?></span>
                                <span class="price-old"><?= e(format_money($rprice)) ?></span>
                            <?php else: ?>
                                <span class="price-current"><?= e(format_money($rprice)) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-actions">
                            <a href="<?= base_url('product.php?id=' . (int)$rp['id']) ?>" class="btn btn-ghost" style="flex:1">View product</a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
