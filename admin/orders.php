<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$pay = $_GET['pay'] ?? '';
$where = '1=1'; $params = [];
if ($q) { $where .= " AND (order_number LIKE ? OR full_name LIKE ? OR phone LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($status) { $where .= " AND order_status = ?"; $params[] = $status; }
if ($pay) { $where .= " AND payment_status = ?"; $params[] = $pay; }
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 20;
$cs = $pdo->prepare("SELECT COUNT(*) c FROM orders WHERE $where");
$cs->execute($params);
$total = (int)$cs->fetch()['c'];
$pages = max(1, (int)ceil($total / $per));
$offset = ($page - 1) * $per;
$stmt = $pdo->prepare("SELECT * FROM orders WHERE $where ORDER BY id DESC LIMIT $per OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll();
admin_header('Orders', 'orders.php');
?>
<div class="toolbar">
    <form method="get">
        <input type="search" name="q" placeholder="Order #, name, phone…" value="<?= e($q) ?>">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach (['pending','approved','processing','shipped','out_for_delivery','delivered','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="pay">
            <option value="">All payments</option>
            <?php foreach (['pending','submitted','verified','failed','cod_pending','cod_received'] as $s): ?>
            <option value="<?= $s ?>" <?= $pay === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>
</div>

<div class="card-block">
    <?php if (!$orders): ?>
    <div class="empty-state"><h2>No orders found</h2><p>Orders will appear here once customers check out.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o):
            $statusClass = match($o['order_status']) {
                'pending','approved','processing' => 'pending',
                'cancelled' => 'failed',
                'delivered' => '',
                default => 'inactive',
            };
            $payClass = match($o['payment_status']) {
                'verified','cod_received' => '',
                'failed','rejected' => 'failed',
                'submitted' => 'review',
                default => 'pending',
            };
        ?>
        <tr>
            <td><strong style="font-family:monospace"><?= e($o['order_number']) ?></strong></td>
            <td class="col-truncate"><?= e($o['full_name']) ?><div style="color:var(--c-muted);font-size:12px"><?= e($o['phone']) ?></div></td>
            <td><strong><?= e(format_money($o['total'])) ?></strong></td>
            <td>
                <span class="status-badge <?= $payClass ?>"><span class="dot"></span><?= e(strtoupper($o['payment_method'])) ?></span>
                <div style="font-size:11px;color:var(--c-muted);margin-top:2px"><?= e(str_replace('_',' ',$o['payment_status'])) ?></div>
            </td>
            <td><span class="status-badge <?= $statusClass ?>"><span class="dot"></span><?= e(ucfirst(str_replace('_',' ',$o['order_status']))) ?></span></td>
            <td><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
            <td><a href="order-view.php?id=<?= (int)$o['id'] ?>" class="btn btn-ghost btn-sm" style="padding:6px 10px;min-height:auto;font-size:12px">View →</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($pages > 1): $base = '?' . http_build_query(array_merge($_GET, ['page' => '__P__'])); ?>
    <div class="pagination">
        <?php if ($page > 1): ?><a href="<?= str_replace('__P__', $page-1, $base) ?>">←</a><?php endif; ?>
        <?php for ($i=1;$i<=$pages;$i++): ?>
            <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
            <?php else: ?><a href="<?= str_replace('__P__', $i, $base) ?>"><?= $i ?></a><?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $pages): ?><a href="<?= str_replace('__P__', $page+1, $base) ?>">→</a><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php admin_footer(); ?>
