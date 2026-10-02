<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$order = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
}
if (!$order) {
    admin_header('Not found', 'orders.php');
    echo '<div class="alert alert-error">Order not found.</div>';
    admin_footer();
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $a = $_POST['_action'] ?? '';
    if ($a === 'update_status' && in_array($_POST['order_status'] ?? '', ['pending','approved','processing','shipped','out_for_delivery','delivered','cancelled'])) {
        $pdo->prepare("UPDATE orders SET order_status=?, updated_at=NOW() WHERE id=?")->execute([$_POST['order_status'], $id]);
    } elseif ($a === 'update_payment' && in_array($_POST['payment_status'] ?? '', ['pending','submitted','verified','failed','refunded','cod_pending','cod_received'])) {
        $pdo->prepare("UPDATE orders SET payment_status=?, updated_at=NOW() WHERE id=?")->execute([$_POST['payment_status'], $id]);
    } elseif ($a === 'add_note') {
        $note = trim($_POST['note'] ?? '');
        if ($note) {
            $existing = $order['notes'] ?? '';
            $pdo->prepare("UPDATE orders SET notes=? WHERE id=?")->execute([$existing . "\n[" . date('Y-m-d H:i') . "] " . $note, $id]);
        }
    }
    redirect(base_url('admin/order-view.php?id=' . $id));
}
$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();
$order = $pdo->query("SELECT * FROM orders WHERE id = " . (int)$id)->fetch();
admin_header('Order ' . $order['order_number'], 'orders.php');
?>
<div class="grid-2" style="grid-template-columns:1.4fr 1fr">
    <div>
        <div class="card-block">
            <h2>Items</h2>
            <table class="data-table">
                <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                <tr>
                    <td class="col-truncate">
                        <strong><?= e($it['name']) ?></strong>
                        <?php if ($it['variant_info']): ?><div style="color:var(--c-muted);font-size:12px"><?= e($it['variant_info']) ?></div><?php endif; ?>
                    </td>
                    <td><?= (int)$it['qty'] ?></td>
                    <td><?= e(format_money($it['price'])) ?></td>
                    <td><strong><?= e(format_money($it['subtotal'])) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="padding:16px 14px;border-top:1px solid var(--c-border);font-size:14px">
                <div class="summary-row"><span class="label">Subtotal</span><span class="value"><?= e(format_money($order['subtotal'])) ?></span></div>
                <?php if ($order['discount'] > 0): ?><div class="summary-row" style="color:var(--c-success)"><span class="label">Discount</span><span class="value">−<?= e(format_money($order['discount'])) ?></span></div><?php endif; ?>
                <div class="summary-row"><span class="label">Shipping</span><span class="value"><?= e(format_money($order['shipping_fee'])) ?></span></div>
                <div class="summary-row total"><span class="label">Total</span><span class="value"><?= e(format_money($order['total'])) ?></span></div>
            </div>
        </div>

        <?php if (!empty($order['transaction_id']) || !empty($order['payment_proof'])): ?>
        <div class="card-block">
            <h2>Payment proof</h2>
            <p style="font-size:13px;color:var(--c-muted);margin-bottom:8px">Transaction ID</p>
            <div style="font-family:monospace;padding:10px 14px;background:#FAFAFA;border:1px solid var(--c-border);border-radius:8px;margin-bottom:14px;word-break:break-all;min-width:0;overflow-wrap:break-word"><?= e($order['transaction_id'] ?: '—') ?></div>
            <?php if (!empty($order['payment_proof'])): ?>
            <p style="font-size:13px;color:var(--c-muted);margin-bottom:8px">Screenshot</p>
            <a href="<?= e(APP_URL . '/' . $order['payment_proof']) ?>" target="_blank"><img src="<?= e(APP_URL . '/' . $order['payment_proof']) ?>" style="max-width:100%;border-radius:10px;border:1px solid var(--c-border)"></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($order['notes'])): ?>
        <div class="card-block">
            <h2>Notes</h2>
            <pre style="white-space:pre-wrap;font-family:inherit;font-size:13px;color:var(--c-text);min-width:0;overflow-wrap:break-word;margin:0"><?= e($order['notes']) ?></pre>
        </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="card-block">
            <h2>Customer</h2>
            <div style="font-size:14px;line-height:1.8;min-width:0;overflow-wrap:break-word">
                <strong><?= e($order['full_name']) ?></strong><br>
                <?= e($order['phone']) ?><br>
                <?php if ($order['email']): ?><?= e($order['email']) ?><br><?php endif; ?>
                <div style="margin-top:8px;color:var(--c-muted);font-size:13px"><?= e($order['address']) ?>, <?= e($order['city']) ?><?= $order['area'] ? ' / ' . e($order['area']) : '' ?><?= $order['postal_code'] ? ' — ' . e($order['postal_code']) : '' ?></div>
            </div>
        </div>

        <div class="card-block">
            <h2>Order status</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="update_status">
                <div class="form-field" style="margin-bottom:14px"><label>Status</label>
                    <select name="order_status">
                        <?php foreach (['pending','approved','processing','shipped','out_for_delivery','delivered','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $order['order_status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary">Update status</button>
            </form>
        </div>

        <div class="card-block">
            <h2>Payment</h2>
            <div style="font-size:14px;margin-bottom:14px;min-width:0;overflow-wrap:break-word">
                <strong>Method:</strong> <?= e(strtoupper($order['payment_method'])) ?><br>
                <strong>Status:</strong> <?= e(ucfirst(str_replace('_',' ',$order['payment_status']))) ?>
            </div>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="update_payment">
                <div class="form-field" style="margin-bottom:14px"><label>Payment status</label>
                    <select name="payment_status">
                        <?php foreach (['pending','submitted','verified','failed','refunded','cod_pending','cod_received'] as $s): ?>
                        <option value="<?= $s ?>" <?= $order['payment_status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary">Update payment</button>
            </form>
        </div>

        <div class="card-block">
            <h2>Add note</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="add_note">
                <div class="form-field" style="margin-bottom:14px"><textarea name="note" rows="3" placeholder="Internal note..."></textarea></div>
                <button class="btn btn-ghost">Add note</button>
            </form>
        </div>
    </div>
</div>
<?php admin_footer(); ?>
