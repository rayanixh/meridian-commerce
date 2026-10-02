<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
admin_header('Logs', 'logs.php');
$logDir = STORAGE_PATH . 'logs/';
$logFile = $logDir . 'app.log';
$content = file_exists($logFile) ? file_get_contents($logFile) : '';
$lines = $content ? array_slice(array_reverse(explode("\n", trim($content))), 0, 200) : [];
?>
<div class="card-block">
    <h2>Application log <span class="pill">last 200 lines</span></h2>
    <?php if (!$lines): ?>
    <div class="empty-state">No log entries yet.</div>
    <?php else: ?>
    <pre style="font-family:monospace;font-size:12px;line-height:1.6;background:#0a0a0a;color:#e0e0e0;padding:18px;border-radius:10px;overflow-x:auto;max-height:600px;overflow-y:auto;min-width:0;white-space:pre-wrap;word-break:break-all"><?= e(implode("\n", $lines)) ?></pre>
    <?php endif; ?>
</div>
<?php admin_footer(); ?>
