<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';
$pdo = db();
$order = null;
$items = [];
$searched = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $orderNumber = trim($_POST['order_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $searched = true;
    if ($orderNumber && $phone) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND phone = ?");
        $stmt->execute([$orderNumber, $phone]);
        $order = $stmt->fetch();
        if ($order) {
            $it = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $it->execute([$order['id']]);
            $items = $it->fetchAll();
        }
    }
}
$pageTitle = 'Track order';
$timeline = [
    'pending' => 'Order placed', 'confirmed' => 'Order confirmed', 'processing' => 'Processing',
    'shipped' => 'Shipped', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered',
];
$orderSteps = array_keys($timeline);
$currentIdx = $order ? array_search($order['order_status'], $orderSteps) : -1;
$paymentLabels = [
    'pending' => 'Payment pending', 'submitted' => 'Payment under review',
    'verified' => 'Payment verified', 'failed' => 'Payment failed', 'refunded' => 'Refunded',
    'cod_pending' => 'Payment pending', 'cod_received' => 'Payment received',
];
?>
<div class="track-page">
    <div class="container">
        <div class="track-card">
            <h1>Track your order</h1>
            <p>Enter your order ID and the phone number used at checkout.</p>

            <?php if ($searched && !$order): ?>
            <div class="alert alert-error">No order found with these details. Please check the order ID and phone number.</div>
            <?php endif; ?>

            <form method="post" style="margin-top:8px">
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="form-field"><label for="order_number">Order ID</label><input type="text" id="order_number" name="order_number" required value="<?= e($_POST['order_number'] ?? '') ?>"></div>
                    <div class="form-field"><label for="phone">Phone</label><input type="text" id="phone" name="phone" required value="<?= e($_POST['phone'] ?? '') ?>"></div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Track →</button>
            </form>

            <?php if ($order): ?>
            <div class="timeline" style="margin-top:30px;padding-top:30px;border-top:1px solid var(--c-border)">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px">
                    <strong>Order <?= e($order['order_number']) ?></strong>
                    <span style="font-size:12px;color:var(--c-muted)"><?= e(date('M j, Y g:i A', strtotime($order['created_at']))) ?></span>
                </div>
                <?php foreach ($timeline as $key => $label):
                    $idx = array_search($key, $orderSteps);
                    $cls = '';
                    if ($currentIdx >= 0 && $idx < $currentIdx) $cls = 'done';
                    elseif ($currentIdx >= 0 && $idx === $currentIdx) $cls = 'current';
                ?>
                <div class="timeline-step <?= $cls ?>">
                    <span class="timeline-dot"><?= $idx <= $currentIdx ? '✓' : '' ?></span>
                    <div class="timeline-info">
                        <div class="timeline-title"><?= e($label) ?></div>
                        <?php if ($idx === $currentIdx): ?><div class="timeline-time">Current status</div><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <div style="padding-top:18px;margin-top:8px;border-top:1px solid var(--c-border)">
                    <h4 style="font-size:11px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:var(--c-muted);margin-bottom:8px">Payment</h4>
                    <div style="font-size:14px"><strong><?= e(strtoupper($order['payment_method'])) ?></strong> — <?= e($paymentLabels[$order['payment_status']] ?? $order['payment_status']) ?></div>
                </div>

                <div style="padding-top:18px;margin-top:8px;border-top:1px solid var(--c-border)">
                    <h4 style="font-size:11px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:var(--c-muted);margin-bottom:8px">Items</h4>
                    <?php foreach ($items as $it): ?>
                    <div style="display:flex;justify-content:space-between;font-size:14px;padding:6px 0;min-width:0;gap:8px">
                        <span style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($it['name']) ?> × <?= (int)$it['qty'] ?></span>
                        <span style="font-weight:500;flex-shrink:0"><?= e(format_money($it['subtotal'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div style="display:flex;justify-content:space-between;padding-top:10px;margin-top:6px;border-top:1px solid var(--c-border);font-weight:600">
                        <span>Total</span><span><?= e(format_money($order['total'])) ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
