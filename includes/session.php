<?php
/**
 * Session / auth helpers
 */

function is_logged_in(): bool { return !empty($_SESSION['user_id']); }
function current_user_id(): ?int { return $_SESSION['user_id'] ?? null; }
function require_login(): void {
    if (!is_logged_in()) {
        if (is_ajax()) json_response(['ok' => false, 'error' => 'Login required'], 401);
        $_SESSION['_redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect(base_url('login.php'));
    }
}
function login_user(int $id): void { $_SESSION['user_id'] = $id; session_regenerate_id(true); }
function logout_user(): void { unset($_SESSION['user_id']); session_regenerate_id(true); }

function is_admin(): bool { return !empty($_SESSION['admin_id']); }
function current_admin_id(): ?int { return $_SESSION['admin_id'] ?? null; }
function require_admin(): void {
    if (!is_admin()) {
        if (is_ajax()) json_response(['ok' => false, 'error' => 'Admin auth required'], 401);
        redirect(base_url('admin/login.php'));
    }
}
function login_admin(int $id): void { $_SESSION['admin_id'] = $id; session_regenerate_id(true); }
function logout_admin(): void { unset($_SESSION['admin_id']); session_regenerate_id(true); }

function get_cart(): array { return $_SESSION['cart'] ?? ['items' => [], 'coupon' => null, 'shipping' => null]; }
function save_cart(array $cart): void { $_SESSION['cart'] = $cart; }
function cart_count(): int {
    $c = 0;
    foreach (get_cart()['items'] as $i) $c += (int)($i['qty'] ?? 0);
    return $c;
}
