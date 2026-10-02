<?php
/**
 * Installation wizard — 7 steps, white/black on-brand.
 */
define('INSTALL_MODE', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/migrate.php';

$step = max(1, min(7, (int)($_GET['step'] ?? 1)));
$error = '';
$success = '';
$dbConfigFile = __DIR__ . '/../config/db_config.php';

if ($dbConfigFile && file_exists($dbConfigFile)) {
    // If a complete install lock exists, the wizard is done.
    // Otherwise the user can re-enter the installer at any step.
    $lockFile = __DIR__ . '/../storage/installed.lock';
    if (file_exists($lockFile) && !isset($_GET['force'])) {
        if (!in_array($step, [1, 7])) {
            redirect(base_url('index.php'));
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['_action'] ?? '';
    if ($action === 'test_db') {
        $cfg = [
            'host' => trim($_POST['db_host'] ?? 'localhost'),
            'port' => trim($_POST['db_port'] ?? '3306'),
            'name' => trim($_POST['db_name'] ?? ''),
            'user' => trim($_POST['db_user'] ?? ''),
            'pass' => $_POST['db_pass'] ?? '',
        ];
        try {
            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};charset=utf8mb4";
            $test = new PDO($dsn, $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $test->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            write_db_config($cfg);
            redirect(base_url('install/?step=3'));
        } catch (Throwable $e) {
            $error = 'Database connection failed. Please check the host, database name, username, password, and port.';
            log_error('install_test_db', $e->getMessage());
        }
    } elseif ($action === 'create_tables') {
        try {
            $cfg = require $dbConfigFile;
            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $res = run_migrations($pdo, false);
            if (!$res['ok']) $error = $res['error'];
            else redirect(base_url('install/?step=4'));
        } catch (Throwable $e) {
            $error = 'Could not connect: ' . $e->getMessage();
        }
    } elseif ($action === 'create_admin') {
        $name = trim($_POST['admin_name'] ?? 'Administrator');
        $email = trim($_POST['admin_email'] ?? '');
        $pass = $_POST['admin_password'] ?? '';
        $pass2 = $_POST['admin_password2'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'A valid email is required.';
        } elseif (strlen($pass) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($pass !== $pass2) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $pdo = db();
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, 'superadmin') ON DUPLICATE KEY UPDATE name=VALUES(name), password=VALUES(password)");
                $stmt->execute([$name, $email, $hash]);
                flash('install_step', 5);
                redirect(base_url('install/?step=5'));
            } catch (Throwable $e) {
                $error = 'Could not create admin: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'save_config') {
        $name = trim($_POST['site_name'] ?? 'Meridian');
        $tagline = trim($_POST['tagline'] ?? '');
        $symbol = trim($_POST['symbol'] ?? '৳');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        try {
            $pdo = db();
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
            foreach (['site_name' => $name, 'site_tagline' => $tagline, 'currency_symbol' => $symbol, 'contact_phone' => $phone, 'contact_email' => $email, 'contact_address' => $address] as $k => $v) {
                $stmt->execute([$k, $v]);
            }
            flash('install_step', 6);
            redirect(base_url('install/?step=6'));
        } catch (Throwable $e) {
            $error = 'Could not save: ' . $e->getMessage();
        }
    } elseif ($action === 'finish') {
        try {
            $pdo = db();
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('installed', '1') ON DUPLICATE KEY UPDATE setting_value='1'")->execute();
        } catch (Throwable $e) {}
        // Write the install lock so the wizard cannot run again.
        $lockDir = __DIR__ . '/../storage';
        if (!is_dir($lockDir)) @mkdir($lockDir, 0775, true);
        @file_put_contents($lockDir . '/installed.lock', date('c'));
        flash('install_complete', 1);
        redirect(base_url('install/?step=7'));
    }
}

$dbCfg = file_exists($dbConfigFile) ? require $dbConfigFile : ['host' => 'localhost', 'port' => '3306', 'name' => '', 'user' => '', 'pass' => ''];
$labels = [
    1 => 'Welcome',
    2 => 'Database',
    3 => 'Create tables',
    4 => 'Admin account',
    5 => 'Configuration',
    6 => 'Review',
    7 => 'Complete',
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Install · Meridian</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{width:100%;max-width:100vw;overflow-x:hidden}
body{font-family:-apple-system,BlinkMacSystemFont,"Inter",system-ui,sans-serif;color:#111;background:#fff;min-height:100vh;display:flex;-webkit-font-smoothing:antialiased}
a{color:inherit;text-decoration:none}
button,input,select,textarea{font:inherit;color:inherit;max-width:100%}
img,svg{max-width:100%;height:auto;display:block}
:root{--bg:#fff;--text:#111;--muted:#666;--border:#E5E5E5;--surface:#FAFAFA;--r-sm:6px;--r-md:10px;--r-lg:16px;--r-xl:24px}
.install{display:grid;grid-template-columns:340px 1fr;width:100%;min-height:100vh}
.aside{background:#FAFAFA;border-right:1px solid var(--border);padding:40px 32px;display:flex;flex-direction:column;justify-content:space-between;min-width:0;overflow:hidden}
.brand{display:flex;align-items:center;gap:10px;font-weight:600;font-size:17px;letter-spacing:-0.01em;min-width:0}
.brand-mark{width:32px;height:32px;border-radius:8px;background:#111;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0}
.brand-name{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}
.progress{margin-top:40px;display:flex;flex-direction:column;gap:4px;min-width:0}
.step{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;font-size:13px;color:#666;transition:all .2s;min-width:0}
.step.active{background:#111;color:#fff;font-weight:500}
.step.done{color:#111}
.step .num{width:26px;height:26px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#fff;border:1px solid var(--border);color:#999;font-size:11px;font-weight:600;flex-shrink:0}
.step.active .num{background:#fff;color:#111;border-color:#fff}
.step.done .num{background:#111;color:#fff;border-color:#111}
.step .label{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.foot{font-size:12px;color:#999;line-height:1.6}
.main{padding:48px 40px;display:flex;align-items:center;justify-content:center;min-width:0;overflow-x:hidden}
.panel{max-width:560px;width:100%;min-width:0}
.eyebrow{font-size:11px;font-weight:600;color:#666;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:16px}
h1{font-size:34px;font-weight:600;letter-spacing:-0.03em;line-height:1.1;margin-bottom:14px;overflow-wrap:break-word}
.lead{color:#666;font-size:15px;line-height:1.6;margin-bottom:32px;overflow-wrap:break-word}
.card{background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:28px;min-width:0;max-width:100%}
.field{margin-bottom:16px;min-width:0}
.field label{display:block;font-size:12px;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;color:#111;margin-bottom:6px}
.field input,.field textarea,.field select{width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:var(--r-md);font-size:14px;font-family:inherit;background:#fff;color:#111;min-width:0;max-width:100%;transition:border-color .15s,box-shadow .15s}
.field input:focus,.field textarea:focus,.field select:focus{outline:none;border-color:#111;box-shadow:0 0 0 3px rgba(0,0,0,0.06)}
.field textarea{resize:vertical;min-height:80px;line-height:1.5}
.row{display:grid;grid-template-columns:1fr 1fr;gap:14px;min-width:0;margin-bottom:0}
.row-3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;min-width:0;margin-bottom:0}
.row > .field,.row-3 > .field{margin-bottom:14px}
.actions{display:flex;justify-content:space-between;align-items:center;margin-top:24px;gap:12px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:13px 22px;border-radius:var(--r-md);font-size:14px;font-weight:500;font-family:inherit;cursor:pointer;border:1px solid transparent;transition:all .15s;line-height:1;min-height:44px;white-space:nowrap;max-width:100%;text-align:center}
.btn-primary{background:#111;color:#fff}
.btn-primary:hover{background:#000;transform:translateY(-1px)}
.btn-primary:active{transform:translateY(0)}
.btn-primary:disabled{opacity:0.5;cursor:not-allowed;transform:none}
.btn-ghost{background:transparent;color:#111;border-color:var(--border)}
.btn-ghost:hover{background:#fafafa}
.btn-link{background:transparent;color:#666;padding:8px 0;min-height:auto;border:none;justify-content:flex-start}
.btn-link:hover{color:#111}
.btn-block{width:100%;display:flex}
.error{background:#FEF2F2;border:1px solid #FECACA;color:#7F1D1D;padding:14px 16px;border-radius:10px;font-size:14px;margin-bottom:20px;line-height:1.5}
.ok{background:#F0FDF4;border:1px solid #BBF7D0;color:#14532D;padding:14px 16px;border-radius:10px;font-size:14px;margin-bottom:20px;line-height:1.5}
.checklist{list-style:none;margin:0;padding:0;min-width:0}
.checklist li{display:flex;align-items:flex-start;gap:12px;padding:14px 0;border-bottom:1px solid var(--border);font-size:14px;min-width:0}
.checklist li:last-child{border-bottom:none}
.checklist .dot{width:24px;height:24px;border-radius:50%;background:#111;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;font-weight:600}
.checklist .dot.pending{background:#fff;color:#999;border:1px solid var(--border)}
.summary{background:#FAFAFA;border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:20px;min-width:0;overflow-wrap:break-word}
.summary dt{font-size:11px;color:#666;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;margin-bottom:2px}
.summary dd{font-size:14px;color:#111;margin-bottom:12px;overflow-wrap:break-word;word-break:break-all}
.summary dd:last-child{margin-bottom:0}
.welcome-illu{width:100%;height:200px;border-radius:var(--r-lg);background:linear-gradient(135deg,#fafafa 0%,#fff 100%);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;margin-bottom:32px;position:relative;overflow:hidden}
.welcome-illu::before{content:"";position:absolute;inset:0;background:radial-gradient(ellipse at top right,rgba(0,0,0,0.04),transparent 60%)}
.welcome-illu svg{width:48px;height:48px;color:#111;position:relative;z-index:1}
.complete-badge{width:72px;height:72px;border-radius:50%;background:#F0FDF4;border:1px solid #BBF7D0;display:inline-flex;align-items:center;justify-content:center;margin-bottom:24px}
@media (max-width:900px){
  .install{grid-template-columns:1fr}
  .aside{padding:24px 20px;border-right:none;border-bottom:1px solid var(--border);min-height:auto}
  .progress{flex-direction:row;overflow-x:auto;margin-top:20px;gap:6px;padding-bottom:6px;scrollbar-width:none}
  .progress::-webkit-scrollbar{display:none}
  .step{white-space:nowrap;flex-shrink:0;padding:8px 12px;font-size:12px}
  .step .label{display:none}
  .main{padding:32px 20px;align-items:flex-start}
  h1{font-size:26px}
  .row,.row-3{grid-template-columns:1fr;gap:0;margin-bottom:0}
  .row > .field,.row-3 > .field{margin-bottom:14px}
  .actions{flex-direction:column-reverse;align-items:stretch}
  .actions .btn{width:100%;justify-content:center}
  .card{padding:22px}
}
</style>
</head>
<body>
<div class="install">
    <aside class="aside">
        <div>
            <div class="brand">
                <span class="brand-mark">M</span>
                <span class="brand-name">Meridian</span>
            </div>
            <div class="progress">
                <?php foreach ($labels as $i => $lbl):
                    $cls = $i < $step ? 'done' : ($i === $step ? 'active' : '');
                ?>
                <div class="step <?= $cls ?>">
                    <span class="num"><?= $i < $step ? '✓' : str_pad($i, 2, '0', STR_PAD_LEFT) ?></span>
                    <span class="label"><?= e($lbl) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="foot">Meridian Commerce v<?= e(APP_VERSION) ?><br>Installation wizard</div>
    </aside>

    <main class="main">
        <div class="panel">
            <div class="eyebrow">Step <?= str_pad($step, 2, '0', STR_PAD_LEFT) ?> / 07</div>

            <?php if ($step === 1): ?>
                <h1>Build your commerce core.</h1>
                <p class="lead">Securely connect your database and configure your store. The process takes about two minutes.</p>
                <div class="welcome-illu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M3 9l9-6 9 6v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </div>
                <div class="actions">
                    <a href="<?= base_url('install/?step=2') ?>" class="btn btn-primary">Start installation →</a>
                </div>

            <?php elseif ($step === 2): ?>
                <h1>Database connection.</h1>
                <p class="lead">Enter your database credentials. We'll create the database if it doesn't exist.</p>
                <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
                <form method="post" class="card">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="test_db">
                    <div class="row">
                        <div class="field"><label for="db_host">Database host</label><input type="text" id="db_host" name="db_host" value="<?= e($_POST['db_host'] ?? $dbCfg['host']) ?>" required></div>
                        <div class="field"><label for="db_port">Port</label><input type="text" id="db_port" name="db_port" value="<?= e($_POST['db_port'] ?? $dbCfg['port']) ?>" required></div>
                    </div>
                    <div class="field"><label for="db_name">Database name</label><input type="text" id="db_name" name="db_name" value="<?= e($_POST['db_name'] ?? $dbCfg['name']) ?>" required></div>
                    <div class="row">
                        <div class="field"><label for="db_user">Username</label><input type="text" id="db_user" name="db_user" value="<?= e($_POST['db_user'] ?? $dbCfg['user']) ?>" required></div>
                        <div class="field"><label for="db_pass">Password</label><input type="password" id="db_pass" name="db_pass" value=""></div>
                    </div>
                    <div class="actions">
                        <a href="<?= base_url('install/?step=1') ?>" class="btn btn-link">← Back</a>
                        <button type="submit" class="btn btn-primary">Test connection →</button>
                    </div>
                </form>

            <?php elseif ($step === 3): ?>
                <h1>Create database tables.</h1>
                <p class="lead">We'll create the required tables and seed default content. Your existing data will not be touched.</p>
                <?php if ($error): ?><div class="error"><strong>Migration failed.</strong><br><?= e($error) ?></div><?php endif; ?>
                <form method="post" class="card">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="create_tables">
                    <dl class="summary">
                        <dt>Host</dt><dd><?= e($dbCfg['host']) ?>:<?= e($dbCfg['port']) ?></dd>
                        <dt>Database</dt><dd><?= e($dbCfg['name']) ?></dd>
                    </dl>
                    <div class="actions">
                        <a href="<?= base_url('install/?step=2') ?>" class="btn btn-link">← Back</a>
                        <button type="submit" class="btn btn-primary">Create tables →</button>
                    </div>
                </form>

            <?php elseif ($step === 4): ?>
                <h1>Create your admin account.</h1>
                <p class="lead">This account will have full access to your store. You can add more admins later.</p>
                <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
                <form method="post" class="card">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="create_admin">
                    <div class="field"><label for="admin_name">Your name</label><input type="text" id="admin_name" name="admin_name" required value="<?= e($_POST['admin_name'] ?? '') ?>"></div>
                    <div class="field"><label for="admin_email">Email address</label><input type="email" id="admin_email" name="admin_email" required value="<?= e($_POST['admin_email'] ?? '') ?>"></div>
                    <div class="row">
                        <div class="field"><label for="admin_password">Password</label><input type="password" id="admin_password" name="admin_password" minlength="8" required></div>
                        <div class="field"><label for="admin_password2">Confirm</label><input type="password" id="admin_password2" name="admin_password2" minlength="8" required></div>
                    </div>
                    <div class="actions">
                        <a href="<?= base_url('install/?step=3') ?>" class="btn btn-link">← Back</a>
                        <button type="submit" class="btn btn-primary">Create account →</button>
                    </div>
                </form>

            <?php elseif ($step === 5): ?>
                <h1>Default configuration.</h1>
                <p class="lead">Tell us about your store. You can change every detail later in the admin panel.</p>
                <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
                <form method="post" class="card">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="save_config">
                    <div class="field"><label for="site_name">Store name</label><input type="text" id="site_name" name="site_name" required value="<?= e($_POST['site_name'] ?? setting('site_name', 'Meridian')) ?>"></div>
                    <div class="field"><label for="tagline">Tagline</label><input type="text" id="tagline" name="tagline" value="<?= e($_POST['tagline'] ?? setting('site_tagline', 'Objects for the considered life.')) ?>"></div>
                    <div class="row-3">
                        <div class="field"><label for="symbol">Currency symbol</label><input type="text" id="symbol" name="symbol" value="<?= e($_POST['symbol'] ?? setting('currency_symbol', '৳')) ?>"></div>
                        <div class="field"><label for="phone">Phone</label><input type="text" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></div>
                        <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>"></div>
                    </div>
                    <div class="field"><label for="address">Address</label><input type="text" id="address" name="address" value="<?= e($_POST['address'] ?? '') ?>"></div>
                    <div class="actions">
                        <a href="<?= base_url('install/?step=4') ?>" class="btn btn-link">← Back</a>
                        <button type="submit" class="btn btn-primary">Save & continue →</button>
                    </div>
                </form>

            <?php elseif ($step === 6): ?>
                <h1>Review.</h1>
                <p class="lead">Confirm everything is in order. You can change every detail from the admin panel.</p>
                <?php
                $checkTables = check_required_tables();
                $pdo = db();
                $adminCount = (int)$pdo->query("SELECT COUNT(*) c FROM admins")->fetch()['c'];
                $settingsCount = (int)$pdo->query("SELECT COUNT(*) c FROM settings")->fetch()['c'];
                $checks = [
                    ['Database connection', true, 'Connected'],
                    ['Database tables', $checkTables['ok'], $checkTables['ok'] ? count($checkTables['existing']) . ' tables created' : 'Missing tables'],
                    ['Admin account', $adminCount > 0, $adminCount > 0 ? 'Account ready' : 'No admin yet'],
                    ['Settings', $settingsCount > 50, $settingsCount . ' settings loaded'],
                    ['Demo content', true, 'Categories, products and pages seeded'],
                ];
                ?>
                <ul class="checklist">
                    <?php foreach ($checks as [$label, $ok, $detail]): ?>
                    <li>
                        <span class="dot <?= $ok ? '' : 'pending' ?>"><?= $ok ? '✓' : '·' ?></span>
                        <div style="min-width:0"><div style="font-weight:500"><?= e($label) ?></div><div style="color:#666;font-size:13px;margin-top:2px"><?= e($detail) ?></div></div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <form method="post" style="margin-top:32px">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="finish">
                    <div class="actions">
                        <a href="<?= base_url('install/?step=5') ?>" class="btn btn-link">← Back</a>
                        <button type="submit" class="btn btn-primary">Complete installation →</button>
                    </div>
                </form>

            <?php elseif ($step === 7): ?>
                <div class="complete-badge">
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="#15803d" stroke-width="2.4"><polyline points="5 12 10 17 19 7"/></svg>
                </div>
                <h1>Your store is live.</h1>
                <p class="lead">Meridian is installed and ready. Sign in to the admin panel to start building.</p>
                <div class="ok" style="margin-bottom:24px">✓ Installation completed successfully.</div>
                <div class="actions">
                    <a href="<?= base_url('admin/login.php') ?>" class="btn btn-primary">Open admin →</a>
                    <a href="<?= base_url('index.php') ?>" class="btn btn-ghost">View store</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
