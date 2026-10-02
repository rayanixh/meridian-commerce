<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$wa = setting('whatsapp_enabled', '0') === '1';
$tg = setting('telegram_enabled', '0') === '1';
$ms = setting('messenger_enabled', '0') === '1';
admin_header('Notifications', 'notifications.php');
?>
<div class="card-block">
    <h2>Status</h2>
    <div class="grid-3">
        <div style="padding:18px;border:1px solid var(--c-border);border-radius:12px;min-width:0">
            <div style="font-size:11px;font-weight:600;color:var(--c-muted);text-transform:uppercase;margin-bottom:8px">WhatsApp</div>
            <span class="status-badge <?= $wa ? '' : 'inactive' ?>"><span class="dot"></span><?= $wa ? 'Enabled' : 'Disabled' ?></span>
            <div style="margin-top:12px"><a href="whatsapp.php" class="btn btn-ghost btn-sm" style="padding:8px 12px;min-height:auto;font-size:12px">Configure →</a></div>
        </div>
        <div style="padding:18px;border:1px solid var(--c-border);border-radius:12px;min-width:0">
            <div style="font-size:11px;font-weight:600;color:var(--c-muted);text-transform:uppercase;margin-bottom:8px">Telegram</div>
            <span class="status-badge <?= $tg ? '' : 'inactive' ?>"><span class="dot"></span><?= $tg ? 'Enabled' : 'Disabled' ?></span>
            <div style="margin-top:12px"><a href="telegram.php" class="btn btn-ghost btn-sm" style="padding:8px 12px;min-height:auto;font-size:12px">Configure →</a></div>
        </div>
        <div style="padding:18px;border:1px solid var(--c-border);border-radius:12px;min-width:0">
            <div style="font-size:11px;font-weight:600;color:var(--c-muted);text-transform:uppercase;margin-bottom:8px">Messenger</div>
            <span class="status-badge <?= $ms ? '' : 'inactive' ?>"><span class="dot"></span><?= $ms ? 'Enabled' : 'Disabled' ?></span>
            <div style="margin-top:12px"><a href="messenger.php" class="btn btn-ghost btn-sm" style="padding:8px 12px;min-height:auto;font-size:12px">Configure →</a></div>
        </div>
    </div>
</div>
<?php admin_footer(); ?>
