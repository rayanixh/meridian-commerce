<?php
/**
 * CSRF protection
 */

function csrf_token(): string {
    return $_SESSION['csrf_token'] ?? '';
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(): void {
    $token = $_POST['_csrf'] ?? $_GET['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!verify_csrf($token)) {
        if (is_ajax()) {
            json_response(['ok' => false, 'error' => 'Invalid CSRF token. Please refresh and try again.'], 419);
        }
        http_response_code(419);
        die('Invalid CSRF token. Please refresh the page and try again.');
    }
}
