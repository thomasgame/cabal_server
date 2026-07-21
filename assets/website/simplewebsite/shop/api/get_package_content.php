<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$item_id = $_GET['item_id'] ?? null;

if (!$item_id) {
    echo json_encode(['success' => false, 'error' => 'Missing item_id']);
    exit;
}

// Check if this is a package item
$stmt = $connShop->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item || !$item['is_package']) {
    echo json_encode(['success' => false, 'error' => 'Not a valid package item']);
    exit;
}

$package_id = $item['package_id'];

$stmt = $connShop->prepare("SELECT item_id, item_name, item_opt, quantity FROM package_items WHERE package_item_id = ?");
$stmt->execute([$package_id]);
$package_contents = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $package_contents]);
