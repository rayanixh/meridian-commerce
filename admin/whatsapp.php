<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach (['whatsapp_enabled','whatsapp_api_url','whatsapp_api_token','whatsapp_recipient'] as $k) {
        if (isset($_POST[$k])) {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$k, (string)$_POST[$k]]);
        }
    }
    redirect(base_url('admin/whatsapp.php?saved=1'));
}
admin_header('WhatsApp notifications', 'whatsapp.php');
?>
<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Saved.</div><?php endif; ?>
<form method="post" class="card-block" style="max-width:680px">
    <?= csrf_field() ?>
    <h2>WhatsApp</h2>
    <p style="font-size:13px;color:var(--c-muted);margin-bottom:14px">Send a WhatsApp notification when a new order is placed.</p>
    <div class="form-field" style="margin-bottom:14px"><label>Enable</label>
        <select name="whatsapp_enabled">
            <option value="0" <?= setting('whatsapp_enabled', '0') === '0' ? 'selected' : '' ?>>Disabled</option>
            <option value="1" <?= setting('whatsapp_enabled', '0') === '1' ? 'selected' : '' ?>>Enabled</option>
        </select>
    </div>
    <div class="form-field" style="margin-bottom:14px"><label>API URL</label><input type="text" name="whatsapp_api_url" value="<?= e(setting('whatsapp_api_url', '')) ?>" placeholder="https://api.example.com"></div>
    <div class="form-field" style="margin-bottom:14px"><label>API token</label><input type="text" name="whatsapp_api_token" value="<?= e(setting('whatsapp_api_token', '')) ?>" autocomplete="off"></div>
    <div class="form-field" style="margin-bottom:14px"><label>Recipient number (with country code)</label><input type="text" name="whatsapp_recipient" value="<?= e(setting('whatsapp_recipient', '')) ?>" placeholder="+8801XXXXXXXXX"></div>
    <div class="form-actions"><button class="btn btn-primary">Save</button></div>
</form>
<?php admin_footer(); ?>
