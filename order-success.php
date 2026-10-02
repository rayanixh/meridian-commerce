<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';
$orderNumber = $_GET['order'] ?? ($_SESSION['last_order_number'] ?? '');
$pageTitle = 'Order placed';
?>
<div class="order-success">
    <div class="container">
        <div class="order-success-card">
            <div style="width:72px;height:72px;border-radius:50%;background:#F0FDF4;border:1px solid #BBF7D0;display:inline-flex;align-items:center;justify-content:center;margin-bottom:24px">
                <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="#15803d" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="5 12 10 17 19 7"/></svg>
            </div>
            <h1 style="font-size:32px;font-weight:600;letter-spacing:-0.025em;margin-bottom:10px">Thank you for your order</h1>
            <p style="color:var(--c-muted);margin-bottom:24px">We’ve received your order<?= $orderNumber ? ' — <strong style="color:var(--c-text)">' . e($orderNumber) . '</strong>' : '' ?>. We’ll contact you shortly to confirm.</p>
            <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
                <a href="<?= base_url('index.php') ?>" class="btn btn-primary">Continue shopping →</a>
                <a href="<?= base_url('track-order.php?order_number=' . urlencode($orderNumber)) ?>" class="btn btn-ghost">Track order</a>
            </div>
        </div>
    </div>
</div>
<style>.order-success{padding:60px 0 80px}.order-success-card{text-align:center;max-width:560px;margin:0 auto;padding:32px 20px}</style>
<?php require __DIR__ . '/includes/footer.php'; ?>
