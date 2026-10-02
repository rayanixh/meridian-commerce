<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/notifications.php';
header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['_csrf'] ?? '');
if (!verify_csrf($token)) json_response(['ok' => false, 'error' => 'Invalid CSRF token. Please refresh.'], 419);

$errors = [];
foreach (['full_name' => 'Full name', 'phone' => 'Phone', 'address' => 'Address', 'city' => 'City'] as $k => $label) {
    if (empty(trim($data[$k] ?? ''))) $errors[$k] = $label . ' is required';
}
$paymentMethod = trim($data['payment_method'] ?? '');
if (!$paymentMethod) $errors['payment_method'] = 'Choose a payment method';
$shipping = trim($data['shipping'] ?? '');
if (!$shipping) $errors['shipping'] = 'Choose a delivery method';
if ($errors) json_response(['ok' => false, 'errors' => $errors, 'error' => 'Please complete all required fields.']);

$cart = cart_totals();
if (empty($cart['items'])) json_response(['ok' => false, 'error' => 'Your cart is empty.']);

$pdo = db();
foreach ($cart['items'] as $item) {
    $stmt = $pdo->prepare("SELECT stock, name FROM products WHERE id = ?");
    $stmt->execute([$item['product_id']]);
    $pr = $stmt->fetch();
    if (!$pr) json_response(['ok' => false, 'error' => 'Product no longer available.']);
    if ((int)$pr['stock'] < $item['qty']) json_response(['ok' => false, 'error' => 'Not enough stock for ' . $pr['name']]);
}

$txnId = trim($data['transaction_id'] ?? '');
$proof = '';
if ($paymentMethod !== 'cod') {
    if (!$txnId) {
        $errors['transaction_id'] = 'Transaction ID is required for online payment';
        json_response(['ok' => false, 'errors' => $errors, 'error' => 'Transaction ID is required.']);
    }
}

$orderNumber = generate_order_id();
$paymentStatus = 'pending';
if ($paymentMethod === 'cod') $paymentStatus = 'cod_pending';
elseif ($txnId) $paymentStatus = 'submitted';

if (!empty($_FILES['payment_proof'])) {
    $up = handle_proof_upload($_FILES['payment_proof']);
    if ($up['ok']) $proof = $up['path'];
}

$userId = is_logged_in() ? current_user_id() : null;

try {
    $pdo->beginTransaction();
    $ins = $pdo->prepare("INSERT INTO orders (order_number, user_id, full_name, phone, email, address, city, area, postal_code, subtotal, discount, shipping_fee, total, coupon_code, shipping_method, payment_method, transaction_id, payment_proof, order_status, payment_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)");
    $ins->execute([
        $orderNumber, $userId, trim($data['full_name']), trim($data['phone']), trim($data['email'] ?? ''),
        trim($data['address']), trim($data['city']), trim($data['area'] ?? ''), trim($data['postal_code'] ?? ''),
        $cart['subtotal'], $cart['discount'], $cart['shipping'], $cart['total'],
        $cart['coupon']['code'] ?? null, $shipping, $paymentMethod, $txnId, $proof, $paymentStatus, trim($data['notes'] ?? ''),
    ]);
    $orderId = (int)$pdo->lastInsertId();

    $itemIns = $pdo->prepare("INSERT INTO order_items (order_id, product_id, name, sku, price, qty, subtotal, variant_info) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($cart['items'] as $item) {
        $vi = is_array($item['variant']) && $item['variant'] ? implode(' / ', array_map(fn($k,$v) => "$k: $v", array_keys($item['variant']), $item['variant'])) : '';
        $itemIns->execute([$orderId, $item['product_id'], $item['name'], '', $item['price'], $item['qty'], $item['subtotal'], $vi]);
        $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?")->execute([$item['qty'], $item['product_id']]);
    }
    if (!empty($cart['coupon'])) {
        $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?")->execute([$cart['coupon']['code']]);
    }
    $pdo->commit();

    cart_clear();
    $_SESSION['last_order_number'] = $orderNumber;

    $or = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $or->execute([$orderId]);
    $orderData = $or->fetch();
    if ($orderData) notify_new_order($orderData);

    json_response(['ok' => true, 'redirect' => base_url('order-success.php?order=' . urlencode($orderNumber)), 'order_number' => $orderNumber]);
} catch (Throwable $e) {
    $pdo->rollBack();
    log_error('checkout', $e->getMessage());
    json_response(['ok' => false, 'error' => 'Could not place order. Please try again.']);
}

function handle_proof_upload(array $file): array {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return ['ok' => false];
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!in_array($file['type'], $allowed)) return ['ok' => false, 'error' => 'Invalid file type'];
    if ($file['size'] > 5 * 1024 * 1024) return ['ok' => false, 'error' => 'File too large'];
    $ext = $allowed[$file['type']];
    $name = 'proof_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dir = APP_ROOT . '/uploads/payment-proofs/';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $path = $dir . $name;
    if (move_uploaded_file($file['tmp_name'], $path)) return ['ok' => true, 'path' => 'uploads/payment-proofs/' . $name];
    return ['ok' => false];
}
