<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: [];

if ($action === 'track') {
    $orderNumber = trim($_GET['order_number'] ?? '');
    $phone = trim($_GET['phone'] ?? '');
    if (!$orderNumber || !$phone) json_response(['ok' => false, 'error' => 'Please provide both order ID and phone.']);
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND phone = ?");
    $stmt->execute([$orderNumber, $phone]);
    $order = $stmt->fetch();
    if (!$order) json_response(['ok' => false, 'error' => 'No order found.']);
    $it = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $it->execute([$order['id']]);
    json_response(['ok' => true, 'order' => $order, 'items' => $it->fetchAll()]);
}
json_response(['ok' => false, 'error' => 'Unknown action'], 400);
