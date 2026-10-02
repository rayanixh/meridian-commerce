<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $a = $_POST['_action'] ?? 'save';
    if ($a === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $new2 = $_POST['new_password2'] ?? '';
        $admin = admin_user();
        if (!$admin || !password_verify($current, $admin['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $new2) {
            $error = 'New passwords do not match.';
        } else {
            $pdo->prepare("UPDATE admins SET password=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), current_admin_id()]);
            $success = 'Password updated.';
        }
    } else {
        $fields = ['site_name','site_tagline','site_description','currency','currency_symbol','contact_email','contact_phone','contact_address','social_facebook','social_instagram','social_youtube','footer_text'];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$f, (string)$_POST[$f]]);
            }
        }
        foreach (['logo_main','logo_mobile','logo_footer','favicon'] as $f) {
            if (isset($_POST[$f])) {
                $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$f, (string)$_POST[$f]]);
            }
        }
        $success = 'Settings saved.';
    }
    redirect(base_url('admin/settings.php?saved=1'));
}
admin_header('Settings', 'settings.php');
?>
<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Saved.</div><?php endif; ?>

<form method="post" class="card-block" style="max-width:780px">
    <?= csrf_field() ?>
    <h2>General</h2>
    <div class="form-field" style="margin-bottom:14px"><label>Website name</label><input type="text" name="site_name" value="<?= e(setting('site_name', 'Meridian')) ?>"></div>
    <div class="form-field" style="margin-bottom:14px"><label>Tagline</label><input type="text" name="site_tagline" value="<?= e(setting('site_tagline', '')) ?>"></div>
    <div class="form-field" style="margin-bottom:14px"><label>Description</label><textarea name="site_description" rows="2"><?= e(setting('site_description', '')) ?></textarea></div>
    <div class="form-row">
        <div class="form-field"><label>Currency code</label><input type="text" name="currency" value="<?= e(setting('currency', 'BDT')) ?>"></div>
        <div class="form-field"><label>Currency symbol</label><input type="text" name="currency_symbol" value="<?= e(setting('currency_symbol', '৳')) ?>"></div>
    </div>
    <h2 style="margin-top:24px">Contact</h2>
    <div class="form-row-3">
        <div class="form-field"><label>Email</label><input type="email" name="contact_email" value="<?= e(setting('contact_email', '')) ?>"></div>
        <div class="form-field"><label>Phone</label><input type="text" name="contact_phone" value="<?= e(setting('contact_phone', '')) ?>"></div>
        <div class="form-field"><label>Address</label><input type="text" name="contact_address" value="<?= e(setting('contact_address', '')) ?>"></div>
    </div>
    <h2 style="margin-top:24px">Social</h2>
    <div class="form-row-3">
        <div class="form-field"><label>Facebook</label><input type="url" name="social_facebook" value="<?= e(setting('social_facebook', '')) ?>"></div>
        <div class="form-field"><label>Instagram</label><input type="url" name="social_instagram" value="<?= e(setting('social_instagram', '')) ?>"></div>
        <div class="form-field"><label>YouTube</label><input type="url" name="social_youtube" value="<?= e(setting('social_youtube', '')) ?>"></div>
    </div>
    <div class="form-field" style="margin-top:14px"><label>Footer text</label><input type="text" name="footer_text" value="<?= e(setting('footer_text', '')) ?>"></div>

    <h2 style="margin-top:24px">Branding</h2>
    <?php
    $logoFields = [
        'logo_main' => 'Main logo (header)', 'logo_mobile' => 'Mobile logo',
        'logo_footer' => 'Footer logo', 'favicon' => 'Favicon',
    ];
    foreach ($logoFields as $k => $label): ?>
    <div class="form-field" style="margin-bottom:14px"><label><?= e($label) ?></label>
        <div class="uploader" data-field="<?= e($k) ?>" style="padding:8px;display:flex;align-items:center;gap:12px;min-height:64px">
            <input type="file" accept="image/jpeg,image/png,image/webp">
            <input type="hidden" name="<?= e($k) ?>" value="<?= e(setting($k, '')) ?>">
            <?php if (setting($k)): ?>
                <img src="<?= e(APP_URL . '/' . setting($k)) ?>" class="uploader-preview" style="max-height:36px;width:auto;margin:0;border-radius:6px">
            <?php endif; ?>
            <div class="uploader-empty" style="font-size:12px">Click to upload</div>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="form-actions"><button class="btn btn-primary">Save settings</button></div>
</form>

<form method="post" class="card-block" style="max-width:540px">
    <?= csrf_field() ?>
    <input type="hidden" name="_action" value="change_password">
    <h2>Change password</h2>
    <div class="form-field" style="margin-bottom:14px"><label>Current password</label><input type="password" name="current_password" required></div>
    <div class="form-field" style="margin-bottom:14px"><label>New password</label><input type="password" name="new_password" minlength="8" required></div>
    <div class="form-field" style="margin-bottom:14px"><label>Confirm new password</label><input type="password" name="new_password2" minlength="8" required></div>
    <div class="form-actions"><button class="btn btn-primary">Update password</button></div>
</form>
<?php admin_footer(); ?>
