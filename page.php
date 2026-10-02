<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';
$pdo = db();
$slug = $_GET['slug'] ?? '';
$page = null;
if ($slug) {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
    $stmt->execute([$slug]);
    $page = $stmt->fetch();
}
if (!$page) $page = ['title' => 'Page', 'content' => '<p>Coming soon.</p>'];
?>
<div class="container" style="padding:32px 0 80px;max-width:780px;min-width:0">
    <h1 class="section-title" style="font-size:36px;margin-bottom:24px"><?= e($page['title']) ?></h1>
    <div style="font-size:15px;line-height:1.75;color:var(--c-text);min-width:0;overflow-wrap:break-word">
        <?= $page['content'] /* admin-controlled HTML */ ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
