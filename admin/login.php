<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_admin()) redirect(base_url('admin/dashboard.php'));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (is_rate_limited($email)) {
        $error = 'Too many attempts. Please try again in a few minutes.';
    } elseif (attempt_admin_login($email, $pass)) {
        record_login_attempt($email, true);
        try { db()->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([current_admin_id()]); } catch (Throwable $e) {}
        redirect(base_url('admin/dashboard.php'));
    } else {
        record_login_attempt($email, false);
        $error = 'Invalid email or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin · Sign in</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{width:100%;max-width:100vw;overflow-x:hidden}
body{font-family:-apple-system,BlinkMacSystemFont,"Inter",system-ui,sans-serif;background:#fff;color:#111;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;-webkit-font-smoothing:antialiased}
.card{width:100%;max-width:380px;background:#fff;border:1px solid #e5e5e5;border-radius:18px;padding:36px;box-shadow:0 1px 0 rgba(0,0,0,0.02);min-width:0}
.brand{display:flex;align-items:center;gap:10px;margin-bottom:32px}
.brand-mark{width:32px;height:32px;border-radius:8px;background:#111;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0}
.brand-name{font-weight:600;font-size:16px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.eyebrow{font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#666;margin-bottom:8px}
h1{font-size:24px;font-weight:600;letter-spacing:-0.02em;margin-bottom:6px;overflow-wrap:break-word}
.lead{color:#666;font-size:14px;margin-bottom:24px;line-height:1.5}
label{display:block;font-size:12px;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;color:#111;margin-bottom:6px}
input{width:100%;padding:13px 14px;border:1px solid #e5e5e5;border-radius:10px;font-size:14px;font-family:inherit;background:#fff;color:#111;min-width:0;max-width:100%}
input:focus{outline:none;border-color:#111;box-shadow:0 0 0 3px rgba(0,0,0,0.06)}
.field{margin-bottom:14px;min-width:0}
.btn{width:100%;padding:14px 22px;background:#111;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:500;cursor:pointer;margin-top:8px;font-family:inherit;min-height:44px}
.btn:hover{background:#000}
.error{background:#FEF2F2;border:1px solid #FECACA;color:#7F1D1D;padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:18px;line-height:1.5}
.foot{margin-top:24px;text-align:center;font-size:12px;color:#999}
a{color:#111;text-decoration:none}
a:hover{text-decoration:underline}
</style>
</head>
<body>
<form class="card" method="post">
    <div class="brand">
        <span class="brand-mark">M</span>
        <span class="brand-name">Meridian</span>
    </div>
    <div class="eyebrow">Admin sign in</div>
    <h1>Welcome back</h1>
    <p class="lead">Sign in to manage your store.</p>
    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
    </div>
    <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
    </div>
    <button type="submit" class="btn">Sign in →</button>
    <div class="foot">Need help? <a href="<?= base_url('index.php') ?>">View store</a></div>
</form>
</body>
</html>
