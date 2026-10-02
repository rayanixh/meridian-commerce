<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $a = $_POST['_action'] ?? 'save';
    if ($a === 'delete') { $pdo->prepare("DELETE FROM pages WHERE id = ?")->execute([(int)$_POST['id']]); }
    else {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: slugify($title);
        $content = $_POST['content'] ?? '';
        $status = $_POST['status'] ?? 'published';
        if ($title) {
            if ($id > 0) $pdo->prepare("UPDATE pages SET title=?, slug=?, content=?, status=? WHERE id=?")->execute([$title, $slug, $content, $status, $id]);
            else $pdo->prepare("INSERT INTO pages (title, slug, content, status) VALUES (?, ?, ?, ?)")->execute([$title, $slug, $content, $status]);
        }
    }
    redirect(base_url('admin/pages.php'));
}
$pages = $pdo->query("SELECT * FROM pages ORDER BY id DESC")->fetchAll();
$edit = null;
if (isset($_GET['edit'])) foreach ($pages as $p) if ((int)$p['id'] === (int)$_GET['edit']) $edit = $p;
admin_header('Pages', 'pages.php');
?>
<form method="post" class="card-block">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <h2><?= $edit ? 'Edit page' : 'Add page' ?></h2>
    <div class="form-row">
        <div class="form-field"><label>Title *</label><input type="text" name="title" required value="<?= e($edit['title'] ?? '') ?>"></div>
        <div class="form-field"><label>Slug</label><input type="text" name="slug" value="<?= e($edit['slug'] ?? '') ?>" placeholder="auto"></div>
    </div>
    <div class="form-field" style="margin-bottom:14px"><label>Status</label>
        <select name="status">
            <option value="published" <?= ($edit['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="draft" <?= ($edit['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
        </select>
    </div>
    <div class="form-field" style="margin-bottom:0"><label>Content (HTML allowed)</label><textarea name="content" rows="14"><?= e($edit['content'] ?? '') ?></textarea></div>
    <div class="form-actions">
        <a href="pages.php" class="btn btn-ghost">Cancel</a>
        <button class="btn btn-primary"><?= $edit ? 'Save' : 'Create' ?></button>
    </div>
</form>

<div class="card-block">
    <h2>All pages</h2>
    <table class="data-table">
        <thead><tr><th>Title</th><th>Slug</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pages as $p): ?>
        <tr>
            <td><strong><?= e($p['title']) ?></strong></td>
            <td style="font-family:monospace;font-size:12px;color:var(--c-muted)"><?= e($p['slug']) ?></td>
            <td><span class="status-badge <?= $p['status'] === 'published' ? '' : 'inactive' ?>"><span class="dot"></span><?= e(ucfirst($p['status'])) ?></span></td>
            <td>
                <div style="display:flex;gap:4px;justify-content:flex-end">
                    <a href="?edit=<?= (int)$p['id'] ?>" class="btn btn-ghost btn-sm" style="padding:6px 10px;min-height:auto;font-size:12px">Edit</a>
                    <a href="<?= base_url('page.php?slug=' . urlencode($p['slug'])) ?>" target="_blank" class="btn btn-ghost btn-sm" style="padding:6px 10px;min-height:auto;font-size:12px">View</a>
                    <form method="post" style="display:inline" data-confirm="Delete?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
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
