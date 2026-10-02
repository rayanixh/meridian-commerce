<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_admin()) json_response(['ok' => false, 'error' => 'Unauthorized'], 401);

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? '');
if (!verify_csrf($token)) json_response(['ok' => false, 'error' => 'Invalid CSRF token'], 419);

$field = $_GET['field'] ?? 'image';
$dirs = [
    'logo_main' => 'uploads/logos/', 'logo_mobile' => 'uploads/logos/', 'logo_footer' => 'uploads/logos/',
    'favicon'   => 'uploads/favicon/', 'image' => 'uploads/products/', 'gallery' => 'uploads/products/',
    'banner'    => 'uploads/banners/', 'category' => 'uploads/categories/',
    'logo'      => 'uploads/payment-logos/', 'payment_logo' => 'uploads/payment-logos/',
];
$subdir = $dirs[$field] ?? 'uploads/misc/';
$absDir = APP_ROOT . '/' . $subdir;
if (!is_dir($absDir)) @mkdir($absDir, 0775, true);

if (empty($_FILES['file'])) json_response(['ok' => false, 'error' => 'No file uploaded'], 400);
$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) json_response(['ok' => false, 'error' => 'Upload error'], 400);
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!isset($allowed[$mime])) json_response(['ok' => false, 'error' => 'Unsupported file type'], 400);
if ($file['size'] > 8 * 1024 * 1024) json_response(['ok' => false, 'error' => 'File too large (max 8MB)'], 400);

$ext = $allowed[$mime];
$name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
$name = substr($name, 0, 40) . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest = $absDir . $name;
if (!move_uploaded_file($file['tmp_name'], $dest)) json_response(['ok' => false, 'error' => 'Could not save'], 500);

json_response(['ok' => true, 'path' => $subdir . $name, 'url' => APP_URL . '/' . $subdir . $name, 'name' => $name, 'size' => $file['size']]);
