<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach (['telegram_enabled','telegram_bot_token','telegram_chat_id'] as $k) {
        if (isset($_POST[$k])) {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$k, (string)$_POST[$k]]);
        }
    }
    redirect(base_url('admin/telegram.php?saved=1'));
}
admin_header('Telegram notifications', 'telegram.php');
?>
<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Saved.</div><?php endif; ?>
<form method="post" class="card-block" style="max-width:680px">
    <?= csrf_field() ?>
    <h2>Telegram</h2>
    <p style="font-size:13px;color:var(--c-muted);margin-bottom:14px">Send a Telegram message to your chat when a new order arrives.</p>
    <div class="form-field" style="margin-bottom:14px"><label>Enable</label>
        <select name="telegram_enabled">
            <option value="0" <?= setting('telegram_enabled', '0') === '0' ? 'selected' : '' ?>>Disabled</option>
            <option value="1" <?= setting('telegram_enabled', '0') === '1' ? 'selected' : '' ?>>Enabled</option>
        </select>
    </div>
    <div class="form-field" style="margin-bottom:14px"><label>Bot token</label><input type="text" name="telegram_bot_token" value="<?= e(setting('telegram_bot_token', '')) ?>" autocomplete="off"></div>
    <div class="form-field" style="margin-bottom:14px"><label>Chat ID</label><input type="text" name="telegram_chat_id" value="<?= e(setting('telegram_chat_id', '')) ?>"></div>
    <div class="form-actions"><button class="btn btn-primary">Save</button></div>
</form>
<?php admin_footer(); ?>
