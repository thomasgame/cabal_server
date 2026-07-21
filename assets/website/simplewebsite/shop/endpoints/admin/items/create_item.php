<?php
header('Content-Type: application/json');
require_once '../../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['item_id'], $data['subcategory_id'], $data['name'], $data['price'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields."]);
    exit;
}

$sql = "INSERT INTO items (item_id, subcategory_id, name, description, price, item_opt, is_package)
        VALUES (?, ?, ?, ?, ?, ?, 0)";

$params = [
    $data['item_id'],
    $data['subcategory_id'],
    $data['name'],
    $data['description'] ?? '',
    $data['price'],
    $data['item_opt'] ?? null
];

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    echo json_encode(["success" => true]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to insert item."]);
}
