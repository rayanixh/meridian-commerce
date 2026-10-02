<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare("UPDATE payment_methods SET name=?, logo=?, instructions=?, merchant_number=?, api_url=?, api_key=?, app_key=?, secret_key=?, api_user=?, api_pass=?, mode=?, status=?, position=? WHERE id=?")
            ->execute([
                trim($_POST['name'] ?? ''), trim($_POST['logo'] ?? ''), trim($_POST['instructions'] ?? ''),
                trim($_POST['merchant_number'] ?? ''), trim($_POST['api_url'] ?? ''),
                trim($_POST['api_key'] ?? ''), trim($_POST['app_key'] ?? ''),
                trim($_POST['secret_key'] ?? ''), trim($_POST['api_user'] ?? ''),
                trim($_POST['api_pass'] ?? ''), $_POST['mode'] ?? 'manual',
                $_POST['status'] ?? 'active', (int)($_POST['position'] ?? 0), $id
            ]);
    }
    redirect(base_url('admin/payments.php?saved=1'));
}
$methods = $pdo->query("SELECT * FROM payment_methods ORDER BY position ASC")->fetchAll();
admin_header('Payment methods', 'payments.php');
?>
<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Saved.</div><?php endif; ?>
<?php foreach ($methods as $m): ?>
<div class="card-block">
    <h2>
        <?php if (!empty($m['logo'])): ?>
            <img src="<?= e(APP_URL . '/' . $m['logo']) ?>" style="height:28px;width:auto;border-radius:6px">
        <?php else: ?>
            <div class="payment-logo" style="width:42px;height:42px;background:#fafafa;border:1px solid var(--c-border);border-radius:8px;display:inline-flex;align-items:center;justify-content:center;padding:8px;color:var(--c-text)"><?= payment_logo_svg($m['code']) ?></div>
        <?php endif; ?>
        <?= e($m['name']) ?>
        <span class="pill"><?= e(strtoupper($m['code'])) ?></span>
        <span class="status-badge <?= $m['status'] === 'active' ? '' : 'inactive' ?>" style="margin-left:auto"><span class="dot"></span><?= e(ucfirst($m['status'])) ?></span>
    </h2>
    <form method="post" data-autosave>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
        <div class="form-row">
            <div class="form-field"><label>Display name</label><input type="text" name="name" value="<?= e($m['name']) ?>"></div>
            <div class="form-field"><label>Position</label><input type="number" name="position" value="<?= (int)$m['position'] ?>"></div>
        </div>
        <div class="form-field" style="margin-bottom:14px"><label>Instructions shown at checkout</label><textarea name="instructions" rows="2"><?= e($m['instructions']) ?></textarea></div>
        <div class="form-field" style="margin-bottom:14px"><label>Logo</label>
            <div class="uploader" data-field="logo" style="padding:8px;display:flex;align-items:center;gap:12px;min-height:64px">
                <input type="file" accept="image/jpeg,image/png,image/webp">
                <input type="hidden" name="logo" value="<?= e($m['logo']) ?>">
                <?php if (!empty($m['logo'])): ?>
                    <img src="<?= e(APP_URL . '/' . $m['logo']) ?>" class="uploader-preview" style="max-height:40px;width:auto;margin:0;border-radius:6px">
                <?php endif; ?>
                <div class="uploader-empty" style="font-size:12px">Click to upload</div>
            </div>
        </div>

        <?php if ($m['code'] !== 'cod'): ?>
        <h3 style="font-size:12px;font-weight:600;margin:18px 0 12px;letter-spacing:0.06em;text-transform:uppercase;color:var(--c-muted)">Manual details</h3>
        <div class="form-field" style="margin-bottom:14px"><label>Merchant number (shown to customer)</label><input type="text" name="merchant_number" value="<?= e($m['merchant_number']) ?>"></div>
        <?php endif; ?>

        <?php if ($m['code'] !== 'cod'): ?>
        <h3 style="font-size:12px;font-weight:600;margin:18px 0 12px;letter-spacing:0.06em;text-transform:uppercase;color:var(--c-muted)">API configuration (optional)</h3>
        <div class="form-row">
            <div class="form-field"><label>Mode</label>
                <select name="mode">
                    <option value="manual" <?= $m['mode'] === 'manual' ? 'selected' : '' ?>>Manual</option>
                    <option value="api" <?= $m['mode'] === 'api' ? 'selected' : '' ?>>API</option>
                </select>
            </div>
            <div class="form-field"><label>API URL</label><input type="text" name="api_url" value="<?= e($m['api_url']) ?>"></div>
        </div>
        <div class="form-row-3">
            <div class="form-field"><label>API key</label><input type="text" name="api_key" value="<?= e($m['api_key']) ?>" autocomplete="off"></div>
            <div class="form-field"><label>App key</label><input type="text" name="app_key" value="<?= e($m['app_key']) ?>" autocomplete="off"></div>
            <div class="form-field"><label>Secret key</label><input type="text" name="secret_key" value="<?= e($m['secret_key']) ?>" autocomplete="off"></div>
        </div>
        <div class="form-row">
            <div class="form-field"><label>API username</label><input type="text" name="api_user" value="<?= e($m['api_user']) ?>" autocomplete="off"></div>
            <div class="form-field"><label>API password</label><input type="text" name="api_pass" value="<?= e($m['api_pass']) ?>" autocomplete="off"></div>
        </div>
        <?php endif; ?>

        <div class="form-field" style="margin-bottom:14px;max-width:200px"><label>Status</label>
            <select name="status">
                <option value="active" <?= $m['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $m['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <button class="btn btn-primary">Save</button>
    </form>
</div>
<?php endforeach; ?>
<?php admin_footer(); ?>
