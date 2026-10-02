<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $a = $_POST['_action'] ?? 'save';
    if ($a === 'delete') { $pdo->prepare("DELETE FROM navigation_items WHERE id = ?")->execute([(int)$_POST['id']]); }
    else {
        $id = (int)($_POST['id'] ?? 0);
        $location = $_POST['location'] ?? 'desktop';
        $label = trim($_POST['label'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $position = (int)($_POST['position'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        if ($id > 0) $pdo->prepare("UPDATE navigation_items SET location=?, label=?, url=?, icon=?, position=?, status=? WHERE id=?")->execute([$location, $label, $url, $icon, $position, $status, $id]);
        else $pdo->prepare("INSERT INTO navigation_items (location, label, url, icon, position, status) VALUES (?, ?, ?, ?, ?, ?)")->execute([$location, $label, $url, $icon, $position, $status]);
    }
    redirect(base_url('admin/navigation.php'));
}
$items = $pdo->query("SELECT * FROM navigation_items ORDER BY location, position ASC")->fetchAll();
$edit = null;
if (isset($_GET['edit'])) foreach ($items as $it) if ((int)$it['id'] === (int)$_GET['edit']) $edit = $it;
admin_header('Navigation', 'navigation.php');
?>
<form method="post" class="card-block" style="max-width:780px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <h2><?= $edit ? 'Edit menu item' : 'Add menu item' ?></h2>
    <div class="form-row">
        <div class="form-field"><label>Location</label>
            <select name="location">
                <option value="desktop" <?= ($edit['location'] ?? 'desktop') === 'desktop' ? 'selected' : '' ?>>Desktop</option>
                <option value="mobile" <?= ($edit['location'] ?? '') === 'mobile' ? 'selected' : '' ?>>Mobile drawer</option>
                <option value="footer" <?= ($edit['location'] ?? '') === 'footer' ? 'selected' : '' ?>>Footer</option>
                <option value="bottom" <?= ($edit['location'] ?? '') === 'bottom' ? 'selected' : '' ?>>Bottom nav (mobile)</option>
            </select>
        </div>
        <div class="form-field"><label>Position</label><input type="number" name="position" value="<?= (int)($edit['position'] ?? '0') ?>"></div>
    </div>
    <div class="form-row-3">
        <div class="form-field"><label>Label *</label><input type="text" name="label" required value="<?= e($edit['label'] ?? '') ?>"></div>
        <div class="form-field"><label>URL</label><input type="text" name="url" value="<?= e($edit['url'] ?? '') ?>" placeholder="category.php?id=1"></div>
        <div class="form-field"><label>Icon (for bottom nav)</label>
            <select name="icon">
                <?php foreach (['home','grid','search','bag','user','heart','star','sparkle','book'] as $ic): ?>
                <option value="<?= $ic ?>" <?= ($edit['icon'] ?? '') === $ic ? 'selected' : '' ?>><?= e($ic) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-field" style="margin-bottom:14px"><label>Status</label>
        <select name="status">
            <option value="active" <?= ($edit['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($edit['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>
    <div class="form-actions">
        <a href="navigation.php" class="btn btn-ghost">Cancel</a>
        <button class="btn btn-primary"><?= $edit ? 'Save' : 'Add' ?></button>
    </div>
</form>

<div class="card-block">
    <h2>All menu items</h2>
    <table class="data-table">
        <thead><tr><th>Label</th><th>Location</th><th>URL</th><th>Position</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
        <tr>
            <td><strong><?= e($it['label']) ?></strong><?php if ($it['icon']): ?> <span style="color:var(--c-muted);font-size:12px">· <?= e($it['icon']) ?></span><?php endif; ?></td>
            <td><?= e(ucfirst($it['location'])) ?></td>
            <td style="font-family:monospace;font-size:12px;color:var(--c-muted)"><?= e($it['url']) ?></td>
            <td><?= (int)$it['position'] ?></td>
            <td><span class="status-badge <?= $it['status'] === 'active' ? '' : 'inactive' ?>"><span class="dot"></span><?= e(ucfirst($it['status'])) ?></span></td>
            <td>
                <div style="display:flex;gap:4px;justify-content:flex-end">
                    <a href="?edit=<?= (int)$it['id'] ?>" class="btn btn-ghost btn-sm" style="padding:6px 10px;min-height:auto;font-size:12px">Edit</a>
                    <form method="post" style="display:inline" data-confirm="Delete?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                        <button class="btn btn-ghost btn-sm" style="padding:6px 10px;min-height:auto;font-size:12px;color:#b91c1c">×</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php admin_footer(); ?>
