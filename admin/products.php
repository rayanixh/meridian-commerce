<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['_action'] ?? '';
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([(int)($_POST['id'] ?? 0)]);
    } elseif ($action === 'toggle') {
        $pdo->prepare("UPDATE products SET status = IF(status='active','inactive','active') WHERE id = ?")->execute([(int)($_POST['id'] ?? 0)]);
    } elseif ($action === 'duplicate') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if ($p) {
            $pdo->prepare("INSERT INTO products (name, slug, sku, category_id, short_description, description, price, sale_price, stock, main_image, gallery, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')")
                ->execute([
                    $p['name'] . ' (Copy)',
                    $p['slug'] . '-copy-' . substr(bin2hex(random_bytes(2)), 0, 4),
                    $p['sku'] . '-' . substr(bin2hex(random_bytes(2)), 0, 4),
                    $p['category_id'], $p['short_description'], $p['description'],
                    $p['price'], $p['sale_price'], $p['stock'], $p['main_image'], $p['gallery']
                ]);
        }
    }
    redirect(base_url('admin/products.php'));
}

$q = trim($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);
$status = $_GET['status'] ?? '';
$where = "1=1"; $params = [];
if ($q) { $where .= " AND (p.name LIKE ? OR p.sku LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($cat) { $where .= " AND p.category_id = ?"; $params[] = $cat; }
if ($status) { $where .= " AND p.status = ?"; $params[] = $status; }
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 20;
$cs = $pdo->prepare("SELECT COUNT(*) c FROM products p WHERE $where");
$cs->execute($params);
$total = (int)$cs->fetch()['c'];
$pages = max(1, (int)ceil($total / $per));
$offset = ($page - 1) * $per;
$stmt = $pdo->prepare("SELECT p.*, c.name AS cat_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $where ORDER BY p.id DESC LIMIT $per OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$cats = $pdo->query("SELECT * FROM categories ORDER BY position ASC")->fetchAll();

admin_header('Products', 'products.php');
?>
<div class="toolbar">
    <form method="get">
        <input type="search" name="q" placeholder="Search name or SKU…" value="<?= e($q) ?>">
        <select name="cat">
            <option value="">All categories</option>
            <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">All status</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
        </select>
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>
    <a href="product-edit.php" class="btn btn-primary">+ Add product</a>
</div>

<div class="card-block">
    <?php if (!$rows): ?>
    <div class="empty-state"><h2>No products yet</h2><p>Create your first product to start selling.</p><a href="product-edit.php" class="btn btn-primary" style="margin-top:16px">+ Add product</a></div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th></th><th>Product</th><th>SKU</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
            $img = $r['main_image'] ?: 'assets/images/defaults/product.svg';
            $statusClass = $r['status'] === 'active' ? '' : ($r['status'] === 'draft' ? 'inactive' : 'pending');
        ?>
        <tr>
            <td><img src="<?= e(APP_URL . '/' . $img) ?>" alt="" class="row-image"></td>
            <td>
                <a href="<?= base_url('admin/product-edit.php?id=' . (int)$r['id']) ?>" style="font-weight:500;color:var(--c-text)"><?= e($r['name']) ?></a>
            </td>
            <td style="font-family:monospace;font-size:12px;color:var(--c-muted)"><?= e($r['sku']) ?></td>
            <td><?= e($r['cat_name'] ?? '—') ?></td>
            <td>
                <?php if ($r['sale_price']): ?>
                <strong><?= e(format_money($r['sale_price'])) ?></strong>
                <div style="text-decoration:line-through;color:var(--c-muted);font-size:12px"><?= e(format_money($r['price'])) ?></div>
                <?php else: ?>
                <strong><?= e(format_money($r['price'])) ?></strong>
                <?php endif; ?>
            </td>
            <td>
                <span class="status-badge <?= $r['stock'] <= 0 ? 'failed' : ($r['stock'] <= $r['low_stock_threshold'] ? 'pending' : '') ?>">
                    <span class="dot"></span><?= (int)$r['stock'] ?>
                </span>
            </td>
            <td><span class="status-badge <?= $statusClass ?>"><span class="dot"></span><?= e(ucfirst($r['status'])) ?></span></td>
            <td>
                <div style="display:flex;gap:4px;justify-content:flex-end;flex-wrap:wrap">
                    <a href="<?= base_url('admin/product-edit.php?id=' . (int)$r['id']) ?>" class="btn btn-ghost btn-sm" style="padding:6px 10px;min-height:auto;font-size:12px">Edit</a>
                    <form method="post" style="display:inline" data-confirm="Duplicate this product?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_action" value="duplicate">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn btn-ghost btn-sm" style="padding:6px 10px;min-height:auto;font-size:12px">Copy</button>
                    </form>
                    <form method="post" style="display:inline" data-confirm="Delete this product?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn btn-ghost btn-sm" style="padding:6px 10px;min-height:auto;font-size:12px;color:#b91c1c">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($pages > 1): $base = '?' . http_build_query(array_merge($_GET, ['page' => '__P__'])); ?>
    <div class="pagination">
        <?php if ($page > 1): ?><a href="<?= str_replace('__P__', $page-1, $base) ?>">←</a><?php endif; ?>
        <?php for ($i=1;$i<=$pages;$i++): ?>
            <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
            <?php else: ?><a href="<?= str_replace('__P__', $i, $base) ?>"><?= $i ?></a><?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $pages): ?><a href="<?= str_replace('__P__', $page+1, $base) ?>">→</a><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php admin_footer(); ?>
