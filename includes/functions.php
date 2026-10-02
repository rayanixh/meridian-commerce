<?php
/**
 * Global helper functions
 */

function e($value): string {
    if ($value === null) return '';
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function log_error(string $context, string $message): void {
    $dir = STORAGE_PATH . 'logs/';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    @file_put_contents($dir . 'app.log', '[' . date('Y-m-d H:i:s') . '] [' . $context . '] ' . $message . PHP_EOL, FILE_APPEND);
}

function setting(string $key, $default = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $pdo = db();
            if ($pdo) {
                foreach ($pdo->query("SELECT setting_key, setting_value FROM settings") as $row) {
                    $cache[$row['setting_key']] = $row['setting_value'];
                }
            }
        } catch (Throwable $e) {}
    }
    return $cache[$key] ?? $default;
}

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-') ?: substr(md5($text), 0, 8);
}

function t(string $key, string $default = ''): string {
    return setting('text_' . $key, $default);
}

function base_url(string $path = ''): string {
    return APP_URL . '/' . ltrim($path, '/');
}

function upload_url(string $path): string {
    if (empty($path)) return '';
    if (str_starts_with($path, 'http')) return $path;
    return APP_URL . '/' . ltrim($path, '/');
}

function site_logo(string $variant = 'main'): string {
    $path = setting('logo_' . $variant, '');
    if (!empty($path) && file_exists(APP_ROOT . '/' . $path)) {
        return APP_URL . '/' . $path;
    }
    return '';
}

function favicon_url(): string {
    $path = setting('favicon', '');
    if (!empty($path) && file_exists(APP_ROOT . '/' . $path)) {
        return APP_URL . '/' . $path;
    }
    return '';
}

function asset(string $path): string {
    return APP_URL . '/assets/' . ltrim($path, '/');
}

function redirect(string $url): void {
    if (!headers_sent()) {
        header('Location: ' . $url);
    } else {
        echo '<script>location.href=' . json_encode($url) . ';</script>';
    }
    exit;
}

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function flash(string $key, ?string $value = null) {
    if ($value === null) {
        $v = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $v;
    }
    $_SESSION['_flash'][$key] = $value;
}

function old(string $key, $default = '') {
    return $_SESSION['_old'][$key] ?? $default;
}

function keep_old(array $data): void { $_SESSION['_old'] = $data; }

function errors(string $key = null) {
    $errs = $_SESSION['_errors'] ?? [];
    unset($_SESSION['_errors']);
    if ($key === null) return $errs;
    return $errs[$key] ?? null;
}

function keep_errors(array $errs): void { $_SESSION['_errors'] = $errs; }

function generate_order_id(): string {
    return 'MC' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

function format_money($amount, ?string $symbol = null): string {
    $symbol = $symbol ?? setting('currency_symbol', '৳');
    return $symbol . number_format((float)$amount, 2);
}

function human_filesize(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = $bytes > 0 ? floor(log($bytes) / log(1024)) : 0;
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 1) . ' ' . $units[$pow];
}

function is_ajax(): bool {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') return true;
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) return true;
    return false;
}

function payment_logo_svg(string $code): string {
    $c = 'viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"';
    $map = [
        'bkash' => '<svg ' . $c . '><rect x="2.5" y="6" width="19" height="13" rx="2.5"/><path d="M7 11h3M7 14h6"/><circle cx="17" cy="14.5" r="1.2" fill="currentColor" stroke="none"/></svg>',
        'nagad' => '<svg ' . $c . '><path d="M3 8.5A2.5 2.5 0 015.5 6h13A2.5 2.5 0 0121 8.5v7a2.5 2.5 0 01-2.5 2.5h-13A2.5 2.5 0 013 15.5v-7z"/><path d="M3 11h18"/><path d="M8 16h2.5"/></svg>',
        'rocket' => '<svg ' . $c . '><path d="M12 3c2 2 4 5 4 8l-4 3-4-3c0-3 2-6 4-8z"/><circle cx="12" cy="11" r="1.4" fill="currentColor" stroke="none"/></svg>',
        'cod'   => '<svg ' . $c . '><rect x="2.5" y="6" width="19" height="13" rx="2"/><circle cx="12" cy="12.5" r="2.5"/><path d="M6 6v13"/></svg>',
    ];
    return $map[strtolower($code)] ?? ('<svg ' . $c . '><rect x="3" y="6" width="18" height="13" rx="2"/><circle cx="12" cy="12.5" r="1.5" fill="currentColor" stroke="none"/></svg>');
}
