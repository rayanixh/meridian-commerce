<?php
/**
 * Cart logic
 */

function cart_add(int $pid, int $qty, array $variant = []): void {
    $c = get_cart();
    $key = $pid . '-' . substr(md5(json_encode($variant)), 0, 8);
    if (isset($c['items'][$key])) {
        $c['items'][$key]['qty'] += $qty;
    } else {
        $c['items'][$key] = ['product_id' => $pid, 'qty' => max(1, $qty), 'variant' => $variant];
    }
    save_cart($c);
}

function cart_update(string $key, int $qty): void {
    $c = get_cart();
    if (isset($c['items'][$key])) {
        if ($qty <= 0) unset($c['items'][$key]);
        else $c['items'][$key]['qty'] = $qty;
    }
    save_cart($c);
}

function cart_remove(string $key): void {
    $c = get_cart();
    unset($c['items'][$key]);
    save_cart($c);
}

function cart_clear(): void {
    save_cart(['items' => [], 'coupon' => null, 'shipping' => null]);
}

function cart_totals(): array {
    $c = get_cart();
    $pdo = db();
    $subtotal = 0.0;
    $items = [];

    if ($pdo && !empty($c['items'])) {
        $ids = array_values(array_unique(array_map(fn($i) => (int)$i['product_id'], $c['items'])));
        if (count($ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id, name, slug, price, sale_price, stock, main_image FROM products WHERE id IN ($placeholders) AND status = 'active'");
            $stmt->execute($ids);
            $products = [];
            foreach ($stmt as $p) $products[(int)$p['id']] = $p;
        } else { $products = []; }

        foreach ($c['items'] as $key => $it) {
            $p = $products[(int)$it['product_id']] ?? null;
            if (!$p) continue;
            $price = (float)($p['sale_price'] ?: $p['price']);
            $lineTotal = $price * (int)$it['qty'];
            $subtotal += $lineTotal;
            $items[$key] = [
                'key'        => $key,
                'product_id' => (int)$p['id'],
                'name'       => $p['name'],
                'slug'       => $p['slug'],
                'price'      => $price,
                'qty'        => (int)$it['qty'],
                'subtotal'   => $lineTotal,
                'image'      => $p['main_image'] ?: 'assets/images/defaults/product.svg',
                'variant'    => $it['variant'] ?? [],
                'stock'      => (int)$p['stock'],
            ];
        }
    }

    $discount = 0.0;
    $coupon = null;
    if (!empty($c['coupon']) && $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active'");
        $stmt->execute([$c['coupon']]);
        $coupon = $stmt->fetch();
        if ($coupon) {
            $valid = !$coupon['expiry_date'] || strtotime($coupon['expiry_date']) >= time();
            $minOk = (float)$coupon['min_order'] <= 0 || $subtotal >= (float)$coupon['min_order'];
            $useOk = !$coupon['usage_limit'] || (int)$coupon['used_count'] < (int)$coupon['usage_limit'];
            if ($valid && $minOk && $useOk) {
                if ($coupon['type'] === 'percent') {
                    $discount = $subtotal * ((float)$coupon['value'] / 100);
                    if ($coupon['max_discount'] && $discount > (float)$coupon['max_discount']) $discount = (float)$coupon['max_discount'];
                } else {
                    $discount = (float)$coupon['value'];
                }
            } else {
                $coupon = null;
            }
        }
    }

    $shipping = 0.0;
    $method = $c['shipping'] ?? null;
    if ($method === 'inside') $shipping = (float)setting('shipping_inside', 60);
    elseif ($method === 'outside') $shipping = (float)setting('shipping_outside', 120);
    $free = (float)setting('shipping_free_threshold', 0);
    if ($free > 0 && $subtotal >= $free) $shipping = 0;

    return [
        'items'    => $items,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'shipping' => $shipping,
        'total'    => max(0, $subtotal - $discount + $shipping),
        'coupon'   => $coupon,
    ];
}
