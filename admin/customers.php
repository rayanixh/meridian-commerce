<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
$q = trim($_GET['q'] ?? '');
$where = '1=1'; $params = [];
if ($q) { $where .= " AND (full_name LIKE ? OR phone LIKE ? OR email LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }
$stmt = $pdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count, (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.user_id = u.id) AS total_spent FROM users u WHERE $where ORDER BY u.id DESC LIMIT 100");
$stmt->execute($params);
$customers = $stmt->fetchAll();
admin_header('Customers', 'customers.php');
?>
<div class="toolbar">
    <form method="get"><input type="search" name="q" placeholder="Search by name, phone, email…" value="<?= e($q) ?>"><button class="btn btn-primary" type="submit">Search</button></form>
</div>
<div class="card-block">
    <?php if (!$customers): ?>
    <div class="empty-state"><h2>No customers yet</h2><p>Customer accounts are created at checkout.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Orders</th><th>Total spent</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach ($customers as $c): ?>
        <tr>
            <td><strong><?= e($c['full_name']) ?></strong></td>
            <td><?= e($c['phone']) ?></td>
            <td><?= e($c['email'] ?? '—') ?></td>
            <td><?= (int)$c['order_count'] ?></td>
            <td><?= e(format_money($c['total_spent'])) ?></td>
            <td><?= e(date('M j, Y', strtotime($c['created_at']))) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php admin_footer(); ?>
