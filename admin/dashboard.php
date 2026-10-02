<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';

$pdo = db();
$today = date('Y-m-d');
$stats = [
    'total_sales'      => (float)$pdo->query("SELECT COALESCE(SUM(total),0) s FROM orders WHERE order_status != 'cancelled'")->fetch()['s'],
    'today_sales'      => (float)$pdo->query("SELECT COALESCE(SUM(total),0) s FROM orders WHERE order_status != 'cancelled' AND DATE(created_at) = '$today'")->fetch()['s'],
    'orders'           => (int)$pdo->query("SELECT COUNT(*) c FROM orders")->fetch()['c'],
    'pending_orders'   => (int)$pdo->query("SELECT COUNT(*) c FROM orders WHERE order_status IN ('pending','approved','processing')")->fetch()['c'],
    'completed_orders' => (int)$pdo->query("SELECT COUNT(*) c FROM orders WHERE order_status = 'delivered'")->fetch()['c'],
    'cancelled_orders' => (int)$pdo->query("SELECT COUNT(*) c FROM orders WHERE order_status = 'cancelled'")->fetch()['c'],
    'pending_payments' => (int)$pdo->query("SELECT COUNT(*) c FROM orders WHERE payment_status IN ('pending','submitted','cod_pending')")->fetch()['c'],
    'verified_payments'=> (int)$pdo->query("SELECT COUNT(*) c FROM orders WHERE payment_status IN ('verified','cod_received')")->fetch()['c'],
    'cod_pending'      => (int)$pdo->query("SELECT COUNT(*) c FROM orders WHERE payment_method = 'cod' AND payment_status = 'cod_pending'")->fetch()['c'],
    'customers'        => (int)$pdo->query("SELECT COUNT(*) c FROM users")->fetch()['c'],
    'products'         => (int)$pdo->query("SELECT COUNT(*) c FROM products")->fetch()['c'],
    'low_stock'        => (int)$pdo->query("SELECT COUNT(*) c FROM products WHERE stock <= low_stock_threshold AND stock > 0")->fetch()['c'],
];

$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 6")->fetchAll();
$lowStockProducts = $pdo->query("SELECT id, name, stock, low_stock_threshold FROM products WHERE stock <= low_stock_threshold ORDER BY stock ASC LIMIT 6")->fetchAll();

$chart = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $v = (float)$pdo->query("SELECT COALESCE(SUM(total),0) s FROM orders WHERE DATE(created_at) = '$d' AND order_status != 'cancelled'")->fetch()['s'];
    $chart[] = ['day' => date('D', strtotime($d)), 'value' => $v];
}
$maxV = max(array_column($chart, 'value')) ?: 1;

admin_header('Dashboard', 'dashboard.php');
?>
<div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Total sales</div><div class="stat-value"><?= e(format_money($stats['total_sales'])) ?></div><div class="stat-trend">All time</div></div>
    <div class="stat-card"><div class="stat-label">Today’s sales</div><div class="stat-value"><?= e(format_money($stats['today_sales'])) ?></div><div class="stat-trend"><?= e(date('M j, Y')) ?></div></div>
    <div class="stat-card"><div class="stat-label">Orders</div><div class="stat-value"><?= (int)$stats['orders'] ?></div><div class="stat-trend"><?= (int)$stats['pending_orders'] ?> pending</div></div>
    <div class="stat-card"><div class="stat-label">Customers</div><div class="stat-value"><?= (int)$stats['customers'] ?></div><div class="stat-trend">All time</div></div>
    <div class="stat-card"><div class="stat-label">Pending orders</div><div class="stat-value"><?= (int)$stats['pending_orders'] ?></div><div class="stat-trend"><?= (int)$stats['completed_orders'] ?> completed</div></div>
    <div class="stat-card"><div class="stat-label">Pending payments</div><div class="stat-value"><?= (int)$stats['pending_payments'] ?></div><div class="stat-trend"><?= (int)$stats['verified_payments'] ?> verified</div></div>
    <div class="stat-card"><div class="stat-label">COD pending</div><div class="stat-value"><?= (int)$stats['cod_pending'] ?></div><div class="stat-trend">awaiting delivery</div></div>
    <div class="stat-card"><div class="stat-label">Products</div><div class="stat-value"><?= (int)$stats['products'] ?></div><div class="stat-trend <?= $stats['low_stock'] > 0 ? 'down' : '' ?>"><?= (int)$stats['low_stock'] ?> low stock</div></div>
</div>

<div class="grid-2">
    <div class="card-block">
        <h2>Last 7 days <span class="pill">sales</span></h2>
        <div class="chart">
            <svg viewBox="0 0 280 130" preserveAspectRatio="none">
                <?php
                $w = 280; $h = 130; $stepX = $w / max(1, count($chart) - 1);
                $points = [];
                foreach ($chart as $i => $c) {
                    $x = $i * $stepX;
                    $y = $h - 10 - (($c['value'] / $maxV) * ($h - 30));
                    $points[] = ['x' => $x, 'y' => $y, 'day' => $c['day']];
                }
                $path = '';
                foreach ($points as $i => $p) $path .= ($i === 0 ? 'M' : 'L') . $p['x'] . ',' . $p['y'] . ' ';
                $area = $path . 'L' . $w . ',' . $h . ' L0,' . $h . ' Z';
                ?>
                <path d="<?= e($area) ?>" fill="#111" opacity="0.05"/>
                <path d="<?= e($path) ?>" fill="none" stroke="#111" stroke-width="1.5"/>
                <?php foreach ($points as $p): ?>
                <circle cx="<?= e($p['x']) ?>" cy="<?= e($p['y']) ?>" r="3" fill="#111"/>
                <?php endforeach; ?>
            </svg>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:11px;color:var(--c-muted)">
            <?php foreach ($chart as $c): ?><span><?= e($c['day']) ?></span><?php endforeach; ?>
        </div>
    </div>
    <div class="card-block">
        <h2>Low stock</h2>
        <?php if (!$lowStockProducts): ?>
        <div class="empty-state" style="padding:30px 0">All products are well stocked.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Product</th><th>Stock</th><th>Threshold</th></tr></thead>
            <tbody>
            <?php foreach ($lowStockProducts as $p): ?>
            <tr>
                <td><a href="<?= base_url('admin/product-edit.php?id=' . (int)$p['id']) ?>" style="color:var(--c-text);font-weight:500"><?= e($p['name']) ?></a></td>
                <td><span class="status-badge pending"><span class="dot"></span><?= (int)$p['stock'] ?></span></td>
                <td><?= (int)$p['low_stock_threshold'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="card-block">
    <h2>Recent orders <a href="orders.php" class="pill" style="margin-left:auto">View all →</a></h2>
    <?php if (!$recentOrders): ?>
    <div class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 7h14l-1.5 11a2 2 0 01-2 1.7H8.5a2 2 0 01-2-1.7L5 7z"/><path d="M9 7V5a3 3 0 016 0v2"/></svg><h2>No orders yet</h2><p>New orders will appear here.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($recentOrders as $o):
            $statusClass = match($o['order_status']) {
                'pending','approved','processing' => 'pending',
                'cancelled' => 'failed',
                'delivered' => '',
                default => 'inactive',
            };
            $payClass = match($o['payment_status']) {
                'verified','cod_received' => '',
                'rejected','failed' => 'failed',
                'submitted' => 'review',
                default => 'pending',
            };
        ?>
        <tr>
            <td><strong style="font-family:monospace"><?= e($o['order_number']) ?></strong></td>
            <td class="col-truncate"><?= e($o['full_name']) ?><br><span style="color:var(--c-muted);font-size:12px"><?= e($o['phone']) ?></span></td>
            <td><?= e(format_money($o['total'])) ?></td>
            <td><span class="status-badge <?= $payClass ?>"><span class="dot"></span><?= e(strtoupper($o['payment_method'])) ?></span></td>
            <td><span class="status-badge <?= $statusClass ?>"><span class="dot"></span><?= e(ucfirst(str_replace('_',' ',$o['order_status']))) ?></span></td>
            <td><?= e(date('M j', strtotime($o['created_at']))) ?></td>
            <td><a href="order-view.php?id=<?= (int)$o['id'] ?>" class="btn btn-ghost btn-sm" style="padding:6px 12px;min-height:auto;font-size:12px">View</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php admin_footer(); ?>
