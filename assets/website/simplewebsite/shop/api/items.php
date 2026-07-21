<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../db.php';

try {
    // You can adjust this query based on subcategory filter if needed
    $stmt = $connShop->query("SELECT id, item_id, name, price, description, is_package FROM items");
    $items = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $items[] = [
            'id' => (int)$row['id'],
            'item_id' => (int)$row['item_id'],
            'name' => $row['name'],
            'price' => (int)$row['price'],
            'description' => $row['description'] ?? 'No description available.',
            'is_package' => filter_var($row['is_package'], FILTER_VALIDATE_BOOLEAN)
        ];
    }

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load items: ' . $e->getMessage()
    ]);
}
