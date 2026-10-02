<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach (['messenger_enabled','messenger_url'] as $k) {
        if (isset($_POST[$k])) {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$k, (string)$_POST[$k]]);
        }
    }
    redirect(base_url('admin/messenger.php?saved=1'));
}
admin_header('Messenger', 'messenger.php');
?>
<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Saved.</div><?php endif; ?>
<form method="post" class="card-block" style="max-width:680px">
    <?= csrf_field() ?>
    <h2>Messenger</h2>
    <div class="form-field" style="margin-bottom:14px"><label>Enable</label>
        <select name="messenger_enabled">
            <option value="0" <?= setting('messenger_enabled', '0') === '0' ? 'selected' : '' ?>>Disabled</option>
            <option value="1" <?= setting('messenger_enabled', '0') === '1' ? 'selected' : '' ?>>Enabled</option>
        </select>
    </div>
    <div class="form-field" style="margin-bottom:14px"><label>Messenger URL</label><input type="url" name="messenger_url" value="<?= e(setting('messenger_url', '')) ?>" placeholder="https://m.me/yourpage"></div>
    <div class="form-actions"><button class="btn btn-primary">Save</button></div>
</form>
<?php admin_footer(); ?>
