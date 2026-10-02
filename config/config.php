<?php
/**
 * Application configuration & bootstrap
 */

// Error handling - production-safe
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');

// Security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Constants
define('APP_NAME', 'Meridian Commerce');
define('APP_VERSION', '1.0.0');
define('APP_ROOT', dirname(__DIR__));
define('APP_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('APP_TIMEZONE', 'Asia/Dhaka');
define('UPLOAD_PATH', APP_ROOT . '/uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');
define('ASSET_PATH', APP_ROOT . '/assets/');
define('ASSET_URL', APP_URL . '/assets/');
define('STORAGE_PATH', APP_ROOT . '/storage/');
define('LOG_PATH', STORAGE_PATH . 'logs/');
define('CONTAINER_MAX', 1280);

date_default_timezone_set(APP_TIMEZONE);

// Session — hardened
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 7200);
    session_name('MERIDIAN_SESSID');
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load database
require_once __DIR__ . '/database.php';

// Helpers
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/theme.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mail.php';
require_once __DIR__ . '/../includes/notifications.php';
