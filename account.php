<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';
$pdo = db();
$tab = $_GET['tab'] ?? 'orders';

if (!is_logged_in()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'login') {
        require_csrf();
        $phone = trim($_POST['phone'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($phone) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            $u = $stmt->fetch();
            if (!$u) {
                $pdo->prepare("INSERT INTO users (full_name, phone, email) VALUES (?, ?, ?)")->execute([$name ?: 'Customer', $phone, $email]);
                $uid = (int)$pdo->lastInsertId();
            } else { $uid = (int)$u['id']; }
            login_user($uid);
            redirect(base_url('account.php'));
        }
    }
    $pageTitle = 'Account';
    ?>
    <div class="auth-page">
        <div class="auth-card">
            <h1>Welcome</h1>
            <p class="lead" style="color:var(--c-muted);font-size:14px;margin-bottom:24px;line-height:1.5">Continue as a guest or sign in to see your orders.</p>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="login">
                <div class="form-field" style="margin-bottom:14px"><label>Your name</label><input type="text" name="name" required></div>
                <div class="form-field" style="margin-bottom:14px"><label>Phone number</label><input type="tel" name="phone" required></div>
                <div class="form-field" style="margin-bottom:18px"><label>Email (optional)</label><input type="email" name="email"></div>
                <button type="submit" class="btn btn-primary btn-block">Continue →</button>
            </form>
        </div>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$userId = current_user_id();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$orders->execute([$userId]);
$orders = $orders->fetchAll();

$pageTitle = 'Account';
?>
<div class="page-header"><div class="container" style="padding-top:24px">
    <div class="eyebrow">Account</div>
    <h1 class="section-title" style="font-size:30px;margin-top:8px">Hello, <?= e($user['full_name'] ?? '') ?></h1>
</div></div>

<div class="account-page">
    <div class="container">
        <div class="account-grid">
            <aside class="account-side">
                <a class="account-link <?= $tab === 'orders' ? 'active' : '' ?>" href="?tab=orders">Orders</a>
                <a class="account-link <?= $tab === 'profile' ? 'active' : '' ?>" href="?tab=profile">Profile</a>
                <a class="account-link" href="<?= base_url('logout.php') ?>">Log out</a>
            </aside>
            <div>
                <?php if ($tab === 'orders'): ?>
                <div class="account-card">
                    <h2>Your orders</h2>
                    <?php if (!$orders): ?>
                    <div class="empty-state" style="padding:30px 0">
                        <p>No orders yet. <a href="<?= base_url('index.php') ?>" style="color:var(--c-text);font-weight:500">Start shopping</a>.</p>
                    </div>
                    <?php else: foreach ($orders as $o): ?>
                    <div style="padding:16px 0;border-bottom:1px solid var(--c-border);min-width:0">
                        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;min-width:0">
                            <div style="min-width:0">
                                <strong style="font-family:monospace"><?= e($o['order_number']) ?></strong>
                                <div style="font-size:12px;color:var(--c-muted)"><?= e(date('M j, Y', strtotime($o['created_at']))) ?></div>
                            </div>
                            <div style="text-align:right">
                                <div style="font-weight:600"><?= e(format_money($o['total'])) ?></div>
                                <div style="font-size:12px;color:var(--c-muted)"><?= e(ucfirst(str_replace('_',' ',$o['order_status']))) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <?php else: ?>
                <div class="account-card">
                    <h2>Profile</h2>
                    <form method="post" action="<?= base_url('account-update.php') ?>">
                        <?= csrf_field() ?>
                        <div class="form-field" style="margin-bottom:14px"><label>Name</label><input type="text" name="full_name" value="<?= e($user['full_name'] ?? '') ?>" required></div>
                        <div class="form-field" style="margin-bottom:14px"><label>Phone</label><input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>" required></div>
                        <div class="form-field" style="margin-bottom:14px"><label>Email</label><input type="email" name="email" value="<?= e($user['email'] ?? '') ?>"></div>
                        <div class="form-field" style="margin-bottom:18px"><label>Address</label><textarea name="address" rows="3"><?= e($user['address'] ?? '') ?></textarea></div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
