<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['_action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: slugify($name);
        $desc = trim($_POST['description'] ?? '');
        $image = trim($_POST['image'] ?? '');
        $position = (int)($_POST['position'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        if ($name) {
            if ($id > 0) $pdo->prepare("UPDATE categories SET name=?, slug=?, description=?, image=?, position=?, status=? WHERE id=?")->execute([$name, $slug, $desc, $image, $position, $status, $id]);
            else $pdo->prepare("INSERT INTO categories (name, slug, description, image, position, status) VALUES (?, ?, ?, ?, ?, ?)")->execute([$name, $slug, $desc, $image, $position, $status]);
        }
    } elseif ($action === 'delete') { $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([(int)$_POST['id']]); }
    redirect(base_url('admin/categories.php'));
}
$cats = $pdo->query("SELECT * FROM categories ORDER BY position ASC, id ASC")->fetchAll();
$edit = null;
if (isset($_GET['edit'])) foreach ($cats as $c) if ((int)$c['id'] === (int)$_GET['edit']) $edit = $c;
admin_header('Categories', 'categories.php');
?>
<form method="post" class="card-block" style="max-width:680px">
    <?= csrf_field() ?>
    <input type="hidden" name="_action" value="save">
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <h2><?= $edit ? 'Edit category' : 'Add category' ?></h2>
    <div class="form-row">
        <div class="form-field"><label>Name *</label><input type="text" name="name" required value="<?= e($edit['name'] ?? '') ?>"></div>
        <div class="form-field"><label>Slug</label><input type="text" name="slug" value="<?= e($edit['slug'] ?? '') ?>" placeholder="auto"></div>
    </div>
    <div class="form-field" style="margin-bottom:14px"><label>Description</label><textarea name="description" rows="2"><?= e($edit['description'] ?? '') ?></textarea></div>
    <div class="form-row-3">
        <div class="form-field"><label>Position</label><input type="number" name="position" value="<?= e($edit['position'] ?? '0') ?>"></div>
        <div class="form-field"><label>Status</label>
            <select name="status">
                <option value="active" <?= ($edit['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($edit['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="form-field"><label>Image</label>
            <div class="uploader" data-field="category" style="padding:8px;min-height:80px">
                <input type="file" accept="image/jpeg,image/png,image/webp">
                <input type="hidden" name="image" value="<?= e($edit['image'] ?? '') ?>">
                <?php if (!empty($edit['image'])): ?>
                    <img src="<?= e(APP_URL . '/' . $edit['image']) ?>" class="uploader-preview" style="max-height:60px;width:auto;margin:0 auto;border-radius:6px">
                <?php else: ?>
                    <div class="uploader-empty" style="font-size:12px;padding:8px">Click to upload</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <a href="categories.php" class="btn btn-ghost"><?= $edit ? 'Cancel' : 'Reset' ?></a>
        <button type="submit" class="btn btn-primary"><?= $edit ? 'Save' : 'Create' ?></button>
    </div>
</form>

<div class="card-block">
    <h2>All categories</h2>
    <table class="data-table">
        <thead><tr><th>Image</th><th>Name</th><th>Slug</th><th>Position</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($cats as $c): ?>
        <tr>
            <td>
                <?php if (!empty($c['image'])): ?>
                    <img src="<?= e(APP_URL . '/' . $c['image']) ?>" class="row-image">
                <?php else: ?>
                    <div class="row-image" style="display:flex;align-items:center;justify-content:center;color:#bbb"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/></svg></div>
                <?php endif; ?>
            </td>
            <td><strong><?= e($c['name']) ?></strong></td>
            <td style="font-family:monospace;font-size:12px;color:var(--c-muted)"><?= e($c['slug']) ?></td>
            <td><?= (int)$c['position'] ?></td>
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
