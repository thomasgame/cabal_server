<?php
header('Content-Type: application/json');
require_once('../config/db.php');

if (!isset($_GET['subcategory_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing subcategory_id']);
    exit;
}

$subcategory_id = (int) $_GET['subcategory_id'];

try {
    $stmt = $connShop->prepare("
        SELECT 
            id, 
            name, 
            price, 
            COALESCE(description, '') AS description,
            is_package
        FROM items 
        WHERE subcategory_id = ?
    ");
    $stmt->execute([$subcategory_id]);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        $item['id'] = (int) $item['id'];
        $item['price'] = (float) $item['price'];
        $item['is_package'] = (bool) $item['is_package'];
        $item['description'] = trim($item['description']);
    }

    echo json_encode(['success' => true, 'items' => $items]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
