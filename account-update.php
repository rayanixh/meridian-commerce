<?php
require_once __DIR__ . '/config/config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $pdo = db();
    $pdo->prepare("UPDATE users SET full_name=?, phone=?, email=?, address=?, city=?, area=?, postal_code=? WHERE id=?")
        ->execute([
            trim($_POST['full_name'] ?? ''), trim($_POST['phone'] ?? ''), trim($_POST['email'] ?? ''),
            trim($_POST['address'] ?? ''), trim($_POST['city'] ?? ''), trim($_POST['area'] ?? ''),
            trim($_POST['postal_code'] ?? ''), current_user_id(),
        ]);
}
redirect(base_url('account.php?tab=profile'));
