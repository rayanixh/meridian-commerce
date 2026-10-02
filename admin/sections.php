<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $a = $_POST['_action'] ?? '';
    if ($a === 'save') {
        $id = (int)$_POST['id'];
        $status = $_POST['status'] ?? 'enabled';
        $position = (int)($_POST['position'] ?? 0);
        $pdo->prepare("UPDATE homepage_sections SET status=?, position=? WHERE id=?")->execute([$status, $position, $id]);
    } elseif ($a === 'reorder' && is_array($_POST['order'] ?? null)) {
        $pos = 1;
        foreach ($_POST['order'] as $id) { $pdo->prepare("UPDATE homepage_sections SET position=? WHERE id=?")->execute([$pos++, (int)$id]); }
    }
    redirect(base_url('admin/sections.php?saved=1'));
}
$sections = $pdo->query("SELECT * FROM homepage_sections ORDER BY position ASC")->fetchAll();
admin_header('Homepage sections', 'sections.php');
?>
<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Saved.</div><?php endif; ?>
<div class="card-block">
    <h2>Enable / disable / reorder</h2>
    <p style="font-size:13px;color:var(--c-muted);margin-bottom:14px">Lower position = appears first. Disabled sections don't render.</p>
    <table class="data-table">
        <thead><tr><th>Section</th><th>Position</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($sections as $s): ?>
        <tr>
            <td><strong><?= e(ucfirst(str_replace('_',' ', $s['section_key']))) ?></strong></td>
            <td>
                <form method="post" style="display:flex;gap:6px;align-items:center">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="save">
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <input type="number" min="1" name="position" value="<?= (int)$s['position'] ?>" style="width:70px;padding:6px 8px;border:1px solid var(--c-border);border-radius:6px">
            </td>
            <td>
                    <select name="status" style="padding:6px 8px;border:1px solid var(--c-border);border-radius:6px">
                        <option value="enabled" <?= $s['status'] === 'enabled' ? 'selected' : '' ?>>Enabled</option>
                        <option value="disabled" <?= $s['status'] === 'disabled' ? 'selected' : '' ?>>Disabled</option>
                    </select>
            </td>
            <td>
                    <button class="btn btn-primary btn-sm" style="padding:6px 10px;min-height:auto;font-size:12px">Save</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php admin_footer(); ?>
