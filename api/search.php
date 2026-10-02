<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) json_response(['ok' => true, 'results' => []]);

$pdo = db();
if (!$pdo) json_response(['ok' => true, 'results' => []]);

$like = '%' . $q . '%';
$stmt = $pdo->prepare("SELECT id, name, sku, price, sale_price, main_image FROM products WHERE status = 'active' AND (name LIKE ? OR sku LIKE ? OR tags LIKE ?) ORDER BY id DESC LIMIT 8");
$stmt->execute([$like, $like, $like]);
$results = [];
foreach ($stmt as $r) {
    $price = (float)($r['sale_price'] ?: $r['price']);
    $img = $r['main_image'] ?: 'assets/images/defaults/product.svg';
    $results[] = [
        'id' => (int)$r['id'], 'name' => $r['name'], 'sku' => $r['sku'],
        'image' => APP_URL . '/' . $img, 'price_fmt' => format_money($price),
    ];
}
json_response(['ok' => true, 'results' => $results]);
