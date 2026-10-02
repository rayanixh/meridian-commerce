<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/cart.php';
header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['_csrf'] ?? '');
if (!verify_csrf($token)) json_response(['ok' => false, 'error' => 'Invalid CSRF token'], 419);

$action = $data['action'] ?? '';

function cart_to_response(array $totals): array {
    $items = [];
    foreach ($totals['items'] as $k => $it) {
        $items[] = [
            'key' => $k, 'product_id' => $it['product_id'], 'name' => $it['name'],
            'image' => APP_URL . '/' . $it['image'], 'qty' => $it['qty'],
            'variant' => $it['variant'], 'price_fmt' => format_money($it['price']),
            'line_total' => format_money($it['subtotal']),
        ];
    }
    return [
        'ok' => true, 'items' => $items, 'count' => cart_count(),
        'subtotal' => $totals['subtotal'], 'subtotal_fmt' => format_money($totals['subtotal']),
        'discount' => $totals['discount'], 'discount_fmt' => format_money($totals['discount']),
        'shipping' => $totals['shipping'], 'shipping_fmt' => format_money($totals['shipping']),
        'total' => $totals['total'], 'total_fmt' => format_money($totals['total']),
    ];
}

switch ($action) {
    case 'list':   json_response(cart_to_response(cart_totals()));
    case 'add': {
        $pid = (int)($data['product_id'] ?? 0);
        if ($pid <= 0) json_response(['ok' => false, 'error' => 'Invalid product'], 400);
        cart_add($pid, max(1, (int)($data['qty'] ?? 1)), is_array($data['variant'] ?? null) ? $data['variant'] : []);
        json_response(cart_to_response(cart_totals()));
    }
    case 'update': {
        $key = (string)($data['key'] ?? '');
        $qty = (int)($data['qty'] ?? 1);
        cart_update($key, $qty);
        $totals = cart_totals();
        $resp = cart_to_response($totals);
        $resp['qty'] = $totals['items'][$key]['qty'] ?? 0;
        json_response($resp);
    }
    case 'remove': cart_remove((string)($data['key'] ?? '')); json_response(cart_to_response(cart_totals()));
    case 'apply_coupon': {
        $code = strtoupper(trim($data['code'] ?? ''));
        if (!$code) json_response(['ok' => false, 'error' => 'Please enter a coupon code.']);
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active'");
        $stmt->execute([$code]);
        if (!$stmt->fetch()) json_response(['ok' => false, 'error' => 'Invalid coupon code.']);
        $c = get_cart(); $c['coupon'] = $code; save_cart($c);
        json_response(['ok' => true]);
    }
    case 'remove_coupon': {
        $c = get_cart(); $c['coupon'] = null; save_cart($c);
        json_response(['ok' => true]);
    }
    case 'set_shipping': {
        $c = get_cart(); $c['shipping'] = $data['shipping'] ?? null; save_cart($c);
        json_response(['ok' => true]);
    }
    default: json_response(['ok' => false, 'error' => 'Unknown action'], 400);
}
