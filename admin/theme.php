<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $a = $_POST['_action'] ?? 'save';
    if ($a === 'preset') {
        $presets = theme_presets();
        $key = $_POST['preset'] ?? '';
        if (isset($presets[$key])) {
            foreach ($presets[$key]['values'] as $k => $v) {
                $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$k, $v]);
            }
        }
    } else {
        $fields = [
            'theme_bg','theme_surface','theme_card','theme_text','theme_muted','theme_border',
            'theme_button','theme_button_text','theme_accent','theme_header',
            'theme_footer_bg','theme_footer_text',
            'theme_border_radius','theme_button_radius','theme_blur','theme_glass_opacity','theme_shadow','theme_animation',
        ];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$f, (string)$_POST[$f]]);
            }
        }
    }
    redirect(base_url('admin/theme.php?saved=1'));
}
$presets = theme_presets();
admin_header('Theme customizer', 'theme.php');
?>
<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Saved.</div><?php endif; ?>
<div class="card-block">
    <h2>Presets</h2>
    <p style="font-size:13px;color:var(--c-muted);margin-bottom:14px">Apply a curated look. Default is white + black.</p>
    <form method="post" style="display:flex;gap:8px;flex-wrap:wrap">
        <?= csrf_field() ?>
        <input type="hidden" name="_action" value="preset">
        <?php foreach ($presets as $k => $p): ?>
        <button type="submit" name="preset" value="<?= e($k) ?>" class="btn btn-ghost" style="min-width:auto"><?= e($p['label']) ?></button>
        <?php endforeach; ?>
    </form>
</div>
<form method="post" class="card-block">
    <?= csrf_field() ?>
    <h2>Colors</h2>
    <div class="grid-2">
        <?php
        $colorFields = [
            'theme_bg' => 'Background', 'theme_surface' => 'Surface', 'theme_card' => 'Card',
            'theme_text' => 'Text', 'theme_muted' => 'Muted text', 'theme_border' => 'Border',
            'theme_button' => 'Button', 'theme_button_text' => 'Button text', 'theme_accent' => 'Accent',
            'theme_header' => 'Header', 'theme_footer_bg' => 'Footer background', 'theme_footer_text' => 'Footer text',
        ];
        foreach ($colorFields as $k => $label):
            $val = setting($k, '');
        ?>
        <div class="form-field"><label><?= e($label) ?></label>
            <div class="color-input">
                <input type="color" value="<?= e($val) ?>" data-color-pick>
                <input type="text" name="<?= e($k) ?>" value="<?= e($val) ?>">
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <h2 style="margin-top:24px">Geometry</h2>
    <div class="form-row-3">
        <div class="form-field"><label>Border radius (px)</label><input type="number" name="theme_border_radius" min="0" max="40" value="<?= e(setting('theme_border_radius', '12')) ?>"></div>
        <div class="form-field"><label>Button radius (px)</label><input type="number" name="theme_button_radius" min="0" max="40" value="<?= e(setting('theme_button_radius', '10')) ?>"></div>
        <div class="form-field"><label>Shadow</label><input type="number" name="theme_shadow" min="0" max="60" value="<?= e(setting('theme_shadow', '18')) ?>"></div>
    </div>
    <div class="form-row-3">
        <div class="form-field"><label>Blur (px)</label><input type="number" name="theme_blur" min="0" max="40" value="<?= e(setting('theme_blur', '14')) ?>"></div>
        <div class="form-field"><label>Glass opacity</label><input type="number" name="theme_glass_opacity" min="0" max="1" step="0.05" value="<?= e(setting('theme_glass_opacity', '0.85')) ?>"></div>
        <div class="form-field"><label>Animation intensity</label><input type="number" name="theme_animation" min="0" max="2" step="0.1" value="<?= e(setting('theme_animation', '1')) ?>"></div>
    </div>
    <div class="form-actions"><button class="btn btn-primary">Save theme</button></div>
</form>
<?php admin_footer(); ?>
