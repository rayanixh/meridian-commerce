<?php
require_once __DIR__ . '/config/config.php';
if (is_logged_in()) redirect(base_url('account.php'));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $phone = trim($_POST['phone'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($phone) {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $u = $stmt->fetch();
        if (!$u) {
            $pdo->prepare("INSERT INTO users (full_name, phone, email) VALUES (?, ?, ?)")->execute([$name ?: 'Customer', $phone, $email]);
            $uid = (int)$pdo->lastInsertId();
        } else { $uid = (int)$u['id']; }
        login_user($uid);
        $after = $_SESSION['_redirect_after_login'] ?? 'account.php';
        unset($_SESSION['_redirect_after_login']);
        redirect(base_url($after));
    } else { $error = 'Phone number is required.'; }
}
$pageTitle = 'Sign in';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
    <div class="auth-card">
        <h1>Welcome back</h1>
        <p class="lead" style="color:var(--c-muted);font-size:14px;margin-bottom:24px;line-height:1.5">Continue with your phone number to view orders.</p>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-field" style="margin-bottom:14px"><label>Name</label><input type="text" name="name" required></div>
            <div class="form-field" style="margin-bottom:14px"><label>Phone</label><input type="tel" name="phone" required></div>
            <div class="form-field" style="margin-bottom:18px"><label>Email (optional)</label><input type="email" name="email"></div>
            <button type="submit" class="btn btn-primary btn-block">Continue →</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
