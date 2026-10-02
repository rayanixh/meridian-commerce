<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach ($_POST as $k => $v) {
        if ($k === '_csrf') continue;
        if (strpos($k, 'text_') === 0 || strpos($k, 'announcement') === 0) {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$k, (string)$v]);
        }
    }
    redirect(base_url('admin/homepage.php?saved=1'));
}
admin_header('Homepage text', 'homepage.php');
?>
<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Saved.</div><?php endif; ?>
<form method="post" class="card-block" style="max-width:780px">
    <?= csrf_field() ?>
    <h2>Hero</h2>
    <div class="form-field" style="margin-bottom:14px"><label>Eyebrow</label><input type="text" name="text_hero_eyebrow" value="<?= e(t('text_hero_eyebrow', 'The 2026 Collection')) ?>"></div>
    <div class="form-field" style="margin-bottom:14px"><label>Title</label><input type="text" name="text_hero_title" value="<?= e(t('text_hero_title', 'Less noise. More wonder.')) ?>"></div>
    <div class="form-field" style="margin-bottom:14px"><label>Subtitle</label><textarea name="text_hero_subtitle" rows="2"><?= e(t('text_hero_subtitle', 'Premium objects selected to make everyday moments feel extraordinary.')) ?></textarea></div>
    <div class="form-row">
        <div class="form-field"><label>Button 1</label><input type="text" name="text_hero_button1" value="<?= e(t('text_hero_button1', 'Explore collection')) ?>"></div>
        <div class="form-field"><label>Button 2</label><input type="text" name="text_hero_button2" value="<?= e(t('text_hero_button2', 'Discover new arrivals')) ?>"></div>
    </div>
    <h2 style="margin-top:24px">Section headings</h2>
    <?php
    $sections = [
        ['text_section_featured','text_section_featured_sub','Featured','Hand-picked essentials.'],
        ['text_section_new','text_section_new_sub','New arrivals','Just landed in the studio.'],
        ['text_section_best','text_section_best_sub','Best sellers','Loved by the community.'],
        ['text_section_categories','text_section_categories_sub','Shop by category','Find your focus.'],
        ['text_section_intention','text_section_intention_sub','Shop by intention','A guided way to discover.'],
        ['text_section_promo','text_section_promo_sub','Limited edition','Released in small batches.'],
        ['text_section_test','text_section_test_sub','What people say','Real words from real owners.'],
        ['text_section_news','text_section_news_sub','Stay in the loop','Quiet updates. No noise.'],
    ];
    foreach ($sections as [$ek, $sk, $defE, $defS]): ?>
    <div class="form-row">
        <div class="form-field"><label><?= e($defE) ?> (eyebrow)</label><input type="text" name="<?= e($ek) ?>" value="<?= e(t($ek, $defE)) ?>"></div>
        <div class="form-field"><label><?= e($defE) ?> (sub)</label><input type="text" name="<?= e($sk) ?>" value="<?= e(t($sk, $defS)) ?>"></div>
    </div>
    <?php endforeach; ?>
    <h2 style="margin-top:24px">Announcement bar</h2>
    <div class="form-field" style="margin-bottom:14px"><label>Text</label><input type="text" name="announcement_text" value="<?= e(setting('announcement_text', '')) ?>"><div class="hint">Leave blank to hide.</div></div>
    <div class="form-actions"><button class="btn btn-primary">Save</button></div>
</form>
<?php admin_footer(); ?>
