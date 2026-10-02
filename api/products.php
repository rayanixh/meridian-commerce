<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json; charset=utf-8');
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) json_response(['ok' => false, 'error' => 'Invalid id']);
$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) json_response(['ok' => false, 'error' => 'Not found']);
json_response(['ok' => true, 'product' => $product]);
