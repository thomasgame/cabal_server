<?php
header('Content-Type: application/json');
require_once '../../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['item_id'], $data['subcategory_id'], $data['name'], $data['price'], $data['contents'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required package fields."]);
    exit;
}

// Step 1: Insert the package as an item
$sql = "INSERT INTO items (item_id, subcategory_id, name, description, price, is_package)
        VALUES (?, ?, ?, ?, ?, 1)";
$params = [
    $data['item_id'],
    $data['subcategory_id'],
    $data['name'],
    $data['description'] ?? '',
    $data['price']
];

$stmt = sqlsrv_query($conn, $sql, $params);

// Get inserted package ID
if ($stmt) {
    $result = sqlsrv_query($conn, "SELECT SCOPE_IDENTITY() AS id");
    $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
    $packageId = $row['id'];
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to insert package."]);
    exit;
}

// Step 2: Insert each sub-item
$errors = [];
foreach ($data['contents'] as $item) {
    $sql = "INSERT INTO package_items (package_item_id, item_id, item_name, item_opt, quantity)
            VALUES (?, ?, ?, ?, ?)";

    $params = [
        $packageId,
        $item['item_id'],
        $item['item_name'],
        $item['item_opt'] ?? null,
        $item['quantity'] ?? 1
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if (!$stmt) $errors[] = $item['item_name'];
}

echo json_encode([
    "success" => true,
    "package_id" => $packageId,
    "errors" => $errors
]);
