<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $a = $_POST['_action'] ?? 'save';
    if ($a === 'delete') { $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([(int)$_POST['id']]); }
    else {
        $id = (int)($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = $_POST['type'] ?? 'percent';
        $value = (float)($_POST['value'] ?? 0);
        $min_order = (float)($_POST['min_order'] ?? 0);
        $max_discount = $_POST['max_discount'] !== '' ? (float)$_POST['max_discount'] : null;
        $expiry = $_POST['expiry_date'] ?: null;
        $usage_limit = $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null;
        $status = $_POST['status'] ?? 'active';
        if ($code) {
            if ($id > 0) $pdo->prepare("UPDATE coupons SET code=?, type=?, value=?, min_order=?, max_discount=?, expiry_date=?, usage_limit=?, status=? WHERE id=?")->execute([$code, $type, $value, $min_order, $max_discount, $expiry, $usage_limit, $status, $id]);
            else $pdo->prepare("INSERT INTO coupons (code, type, value, min_order, max_discount, expiry_date, usage_limit, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")->execute([$code, $type, $value, $min_order, $max_discount, $expiry, $usage_limit, $status]);
        }
    }
    redirect(base_url('admin/coupons.php'));
}
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
$edit = null;
if (isset($_GET['edit'])) foreach ($coupons as $c) if ((int)$c['id'] === (int)$_GET['edit']) $edit = $c;
admin_header('Coupons', 'coupons.php');
?>
<form method="post" class="card-block" style="max-width:780px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <h2><?= $edit ? 'Edit coupon' : 'Create coupon' ?></h2>
    <div class="form-row-3">
        <div class="form-field"><label>Code *</label><input type="text" name="code" required value="<?= e($edit['code'] ?? '') ?>" style="text-transform:uppercase"></div>
        <div class="form-field"><label>Type</label>
            <select name="type">
                <option value="percent" <?= ($edit['type'] ?? 'percent') === 'percent' ? 'selected' : '' ?>>Percentage</option>
                <option value="fixed" <?= ($edit['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed amount</option>
            </select>
        </div>
        <div class="form-field"><label>Value</label><input type="number" step="0.01" min="0" name="value" value="<?= e($edit['value'] ?? '0') ?>"></div>
    </div>
    <div class="form-row-3">
        <div class="form-field"><label>Minimum order</label><input type="number" step="0.01" min="0" name="min_order" value="<?= e($edit['min_order'] ?? '0') ?>"></div>
        <div class="form-field"><label>Max discount (for %)</label><input type="number" step="0.01" min="0" name="max_discount" value="<?= e($edit['max_discount'] ?? '') ?>"></div>
        <div class="form-field"><label>Usage limit</label><input type="number" min="0" name="usage_limit" value="<?= e($edit['usage_limit'] ?? '') ?>"></div>
    </div>
    <div class="form-row">
        <div class="form-field"><label>Expiry date</label><input type="date" name="expiry_date" value="<?= e($edit['expiry_date'] ?? '') ?>"></div>
        <div class="form-field"><label>Status</label>
            <select name="status">
                <option value="active" <?= ($edit['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($edit['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
    </div>
    <div class="form-actions">
        <a href="coupons.php" class="btn btn-ghost">Cancel</a>
        <button class="btn btn-primary"><?= $edit ? 'Save' : 'Create' ?></button>
    </div>
</form>

<div class="card-block">
    <h2>All coupons</h2>
    <table class="data-table">
        <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Min order</th><th>Used</th><th>Expiry</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($coupons as $c): ?>
        <tr>
            <td><strong style="font-family:monospace"><?= e($c['code']) ?></strong></td>
            <td><?= e(ucfirst($c['type'])) ?></td>
            <td><?= e($c['type'] === 'percent' ? $c['value'] . '%' : format_money($c['value'])) ?></td>
            <td><?= e(format_money($c['min_order'])) ?></td>
            <td><?= (int)$c['used_count'] ?><?= $c['usage_limit'] ? ' / ' . (int)$c['usage_limit'] : '' ?></td>
            <td><?= $c['expiry_date'] ? e(date('M j, Y', strtotime($c['expiry_date']))) : '—' ?></td>
            <td><span class="status-badge <?= $c['status'] === 'active' ? '' : 'inactive' ?>"><span class="dot"></span><?= e(ucfirst($c['status'])) ?></span></td>
            <td>
                <div style="display:flex;gap:4px;justify-content:flex-end">
                    <a href="?edit=<?= (int)$c['id'] ?>" class="btn btn-ghost btn-sm" style="padding:6px 10px;min-height:auto;font-size:12px">Edit</a>
                    <form method="post" style="display:inline" data-confirm="Delete?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <button class="btn btn-ghost btn-sm" style="padding:6px 10px;min-height:auto;font-size:12px;color:#b91c1c">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php admin_footer(); ?>
