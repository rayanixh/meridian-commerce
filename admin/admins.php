<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
$me = admin_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $a = $_POST['_action'] ?? '';
    if ($a === 'invite') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'admin';
        if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($pass) >= 8) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            try { $pdo->prepare("INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, ?)")->execute([$name, $email, $hash, $role]); } catch (Throwable $e) {}
        }
    } elseif ($a === 'delete') {
        $id = (int)$_POST['id'];
        if ($id !== (int)$me['id']) $pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$id]);
    }
    redirect(base_url('admin/admins.php'));
}
$admins = $pdo->query("SELECT * FROM admins ORDER BY id ASC")->fetchAll();
admin_header('Admin team', 'admins.php');
?>
<form method="post" class="card-block" style="max-width:680px">
    <?= csrf_field() ?>
    <input type="hidden" name="_action" value="invite">
    <h2>Add admin</h2>
    <div class="form-row-3">
        <div class="form-field"><label>Name</label><input type="text" name="name" required></div>
        <div class="form-field"><label>Email</label><input type="email" name="email" required></div>
        <div class="form-field"><label>Password (min 8)</label><input type="password" name="password" minlength="8" required></div>
    </div>
    <div class="form-field" style="margin-bottom:14px;max-width:240px"><label>Role</label>
        <select name="role">
            <option value="admin">Admin</option>
            <option value="superadmin">Super admin</option>
        </select>
    </div>
    <div class="form-actions"><button class="btn btn-primary">Add admin</button></div>
</form>

<div class="card-block">
    <h2>All admins</h2>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last login</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($admins as $a): ?>
        <tr>
            <td><strong><?= e($a['name']) ?></strong><?= (int)$a['id'] === (int)$me['id'] ? ' <span style="color:var(--c-muted);font-size:12px">(you)</span>' : '' ?></td>
            <td><?= e($a['email']) ?></td>
            <td><span class="status-badge <?= $a['role'] === 'superadmin' ? '' : 'inactive' ?>"><span class="dot"></span><?= e(ucfirst($a['role'])) ?></span></td>
            <td><?= $a['last_login'] ? e(date('M j, Y g:i A', strtotime($a['last_login']))) : '—' ?></td>
            <td>
                <?php if ((int)$a['id'] !== (int)$me['id']): ?>
                <form method="post" style="display:inline" data-confirm="Remove?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                    <button class="btn btn-ghost btn-sm" style="padding:6px 10px;min-height:auto;font-size:12px;color:#b91c1c">Remove</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php admin_footer(); ?>
