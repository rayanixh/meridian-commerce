<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach ($_POST as $k => $v) {
        if (preg_match('/^stock_(\d+)$/', $k, $m)) {
            $id = (int)$m[1];
            $pdo->prepare("UPDATE products SET stock=? WHERE id=?")->execute([max(0, (int)$v), $id]);
        } elseif (preg_match('/^threshold_(\d+)$/', $k, $m)) {
            $id = (int)$m[1];
            $pdo->prepare("UPDATE products SET low_stock_threshold=? WHERE id=?")->execute([max(0, (int)$v), $id]);
        }
    }
    redirect(base_url('admin/inventory.php'));
}
$filter = $_GET['filter'] ?? 'all';
$where = "1=1";
if ($filter === 'low') $where = "stock <= low_stock_threshold AND stock > 0";
if ($filter === 'out') $where = "stock <= 0";
$products = $pdo->query("SELECT * FROM products WHERE $where ORDER BY stock ASC LIMIT 200")->fetchAll();
admin_header('Inventory', 'inventory.php');
?>
<div class="toolbar">
    <div class="toolbar-actions">
        <a href="?filter=all" class="btn btn-ghost btn-sm" style="padding:8px 14px;min-height:auto;font-size:12px;<?= $filter==='all'?'background:#111;color:#fff':'' ?>">All</a>
        <a href="?filter=low" class="btn btn-ghost btn-sm" style="padding:8px 14px;min-height:auto;font-size:12px;<?= $filter==='low'?'background:#111;color:#fff':'' ?>">Low stock</a>
        <a href="?filter=out" class="btn btn-ghost btn-sm" style="padding:8px 14px;min-height:auto;font-size:12px;<?= $filter==='out'?'background:#111;color:#fff':'' ?>">Out of stock</a>
    </div>
</div>
<form method="post">
    <?= csrf_field() ?>
    <div class="card-block">
        <?php if (!$products): ?>
        <div class="empty-state">No matching products.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Product</th><th>Stock</th><th>Threshold</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><a href="product-edit.php?id=<?= (int)$p['id'] ?>" style="font-weight:500;color:var(--c-text)"><?= e($p['name']) ?></a><div style="color:var(--c-muted);font-size:12px"><?= e($p['sku']) ?></div></td>
                <td><input type="number" min="0" name="stock_<?= (int)$p['id'] ?>" value="<?= (int)$p['stock'] ?>" style="width:80px;padding:6px 8px;border:1px solid var(--c-border);border-radius:6px"></td>
                <td><input type="number" min="0" name="threshold_<?= (int)$p['id'] ?>" value="<?= (int)$p['low_stock_threshold'] ?>" style="width:80px;padding:6px 8px;border:1px solid var(--c-border);border-radius:6px"></td>
                <td>
                    <?php if ($p['stock'] <= 0): ?><span class="status-badge failed"><span class="dot"></span>Out of stock</span>
                    <?php elseif ($p['stock'] <= $p['low_stock_threshold']): ?><span class="status-badge pending"><span class="dot"></span>Low</span>
                    <?php else: ?><span class="status-badge"><span class="dot"></span>In stock</span><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="form-actions"><button class="btn btn-primary">Save stock</button></div>
        <?php endif; ?>
    </div>
</form>
<?php admin_footer(); ?>
