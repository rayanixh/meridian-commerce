<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$category = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND status = 'active'");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
}
$pageTitle = $category['name'] ?? 'Shop all';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$where = "p.status = 'active'";
$params = [];
if ($category) { $where .= " AND p.category_id = ?"; $params[] = $category['id']; }
$orderBy = match($sort) {
    'price_low'  => 'COALESCE(p.sale_price, p.price) ASC',
    'price_high' => 'COALESCE(p.sale_price, p.price) DESC',
    'name'       => 'p.name ASC',
    default      => 'p.id DESC',
};

$cs = $pdo->prepare("SELECT COUNT(*) c FROM products p WHERE $where");
$cs->execute($params);
$total = (int)$cs->fetch()['c'];
$totalPages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT p.*, c.name AS cat_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $where ORDER BY $orderBy LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll();

$cats = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY position ASC")->fetchAll();
?>

<div class="page-header"><div class="container" style="padding-top:24px">
    <div class="eyebrow">Catalog</div>
    <h1 class="section-title" style="font-size:30px;margin-top:8px"><?= e($pageTitle) ?></h1>
</div></div>

<div class="shop-page">
    <div class="container">
        <div class="shop-grid">
            <aside class="shop-sidebar" id="shopSidebar">
                <div class="filter-group">
                    <h4>Categories</h4>
                    <div class="filter-list">
                        <a href="<?= base_url('category.php') ?>" class="filter-list" style="font-size:14px;display:flex;align-items:center;gap:8px;color:<?= !$category ? 'var(--c-text);font-weight:600' : 'var(--c-muted)' ?>">All products</a>
                        <?php foreach ($cats as $c): ?>
                        <a href="<?= base_url('category.php?id=' . (int)$c['id']) ?>" style="font-size:14px;display:flex;align-items:center;gap:8px;color:<?= $category && $category['id'] == $c['id'] ? 'var(--c-text);font-weight:600' : 'var(--c-muted)' ?>">
                            <?= e($c['name']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>

            <div>
                <div class="shop-toolbar">
                    <div class="count"><?= $total ?> product<?= $total === 1 ? '' : 's' ?></div>
                    <form method="get" style="display:flex;align-items:center;gap:8px">
                        <?php if ($category): ?><input type="hidden" name="id" value="<?= (int)$category['id'] ?>"><?php endif; ?>
                        <select name="sort" onchange="this.form.submit()">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: low to high</option>
                            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: high to low</option>
                            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A–Z</option>
                        </select>
                    </form>
                </div>

                <?php if (!$products): ?>
                <div class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg><h2>No products found</h2><p>Try a different category or check back soon.</p></div>
                <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $p):
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

                <?php if ($totalPages > 1): $base = '?' . http_build_query(array_merge($_GET, ['page' => '__PAGE__'])); ?>
                <div class="pagination">
                    <?php if ($page > 1): ?><a href="<?= str_replace('__PAGE__', $page - 1, $base) ?>">←</a><?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
                        <?php else: ?><a href="<?= str_replace('__PAGE__', $i, $base) ?>"><?= $i ?></a><?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?><a href="<?= str_replace('__PAGE__', $page + 1, $base) ?>">→</a><?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
