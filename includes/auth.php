<?php
/**
 * Admin auth helpers
 */

function attempt_admin_login(string $email, string $password): bool {
    $pdo = db();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if (!$admin) return false;
    if (!password_verify($password, $admin['password'])) return false;
    login_admin((int)$admin['id']);
    return true;
}

function admin_user(): ?array {
    if (!is_admin()) return null;
    static $cached = null;
    if ($cached !== null) return $cached;
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
    $stmt->execute([current_admin_id()]);
    $cached = $stmt->fetch() ?: null;
    return $cached;
}

function record_login_attempt(string $email, bool $success): void {
    $pdo = db();
    if (!$pdo) return;
    try {
        $pdo->prepare("INSERT INTO login_attempts (email, ip, success, created_at) VALUES (?, ?, ?, NOW())")
            ->execute([$email, $_SERVER['REMOTE_ADDR'] ?? '', $success ? 1 : 0]);
    } catch (Throwable $e) {}
}

function is_rate_limited(string $email, int $max = 6, int $window = 15): bool {
    $pdo = db();
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM login_attempts WHERE email = ? AND success = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        $stmt->execute([$email, $window]);
        $r = $stmt->fetch();
        return ((int)$r['c']) >= $max;
    } catch (Throwable $e) { return false; }
}
