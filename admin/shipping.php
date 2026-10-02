<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach (['shipping_inside','shipping_outside','shipping_free_threshold'] as $k) {
        if (isset($_POST[$k])) {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$k, (string)(float)$_POST[$k]]);
        }
    }
    redirect(base_url('admin/shipping.php?saved=1'));
}
admin_header('Shipping', 'shipping.php');
?>
<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Saved.</div><?php endif; ?>
<form method="post" class="card-block" style="max-width:600px">
    <?= csrf_field() ?>
    <h2>Delivery fees</h2>
    <div class="form-field" style="margin-bottom:14px"><label>Inside city fee</label><input type="number" step="0.01" min="0" name="shipping_inside" value="<?= e(setting('shipping_inside', '60')) ?>"><div class="hint">Charge for deliveries within your primary city.</div></div>
    <div class="form-field" style="margin-bottom:14px"><label>Outside city fee</label><input type="number" step="0.01" min="0" name="shipping_outside" value="<?= e(setting('shipping_outside', '120')) ?>"><div class="hint">Charge for deliveries outside the primary city.</div></div>
    <div class="form-field" style="margin-bottom:14px"><label>Free shipping threshold</label><input type="number" step="0.01" min="0" name="shipping_free_threshold" value="<?= e(setting('shipping_free_threshold', '3000')) ?>"><div class="hint">Orders at or above this amount ship free. Set 0 to disable.</div></div>
    <div class="form-actions"><button class="btn btn-primary">Save</button></div>
</form>
<?php admin_footer(); ?>
