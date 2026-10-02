<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';
$pdo = db();
$cats = $pdo ? $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY position ASC")->fetchAll() : [];
$pageTitle = 'Categories';
?>
<div class="container" style="padding:24px 0 80px;min-width:0">
    <div class="eyebrow">Browse</div>
    <h1 class="section-title" style="font-size:30px;margin-top:8px;margin-bottom:32px">All categories</h1>
    <div class="category-grid">
        <?php foreach ($cats as $c): ?>
        <a href="<?= base_url('category.php?id=' . (int)$c['id']) ?>" class="category-card">
            <div class="category-image">
                <?php if (!empty($c['image']) && file_exists(APP_ROOT . '/' . $c['image'])): ?>
                    <img src="<?= e(APP_URL . '/' . $c['image']) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="category-placeholder">
                        <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.2"><circle cx="12" cy="12" r="10"/></svg>
                    </div>
                <?php endif; ?>
            </div>
            <div class="category-info">
                <h3 class="category-name"><?= e($c['name']) ?></h3>
                <span class="category-arrow">→</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
