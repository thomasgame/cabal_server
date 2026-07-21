<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$user_num = $_POST['user_num'] ?? null;
$item_id = $_POST['item_id'] ?? null;
$quantity = $_POST['quantity'] ?? 1;

if (!$user_num || !$item_id) {
    echo json_encode(['success' => false, 'error' => 'Missing user_num or item_id']);
    exit;
}

// Fetch item to check if it’s a package or regular item
$item_stmt = sqlsrv_query($conn, "SELECT * FROM items WHERE id = ?", [$item_id]);
$item = sqlsrv_fetch_array($item_stmt, SQLSRV_FETCH_ASSOC);

if (!$item) {
    echo json_encode(['success' => false, 'error' => 'Item not found']);
    exit;
}

$is_package = $item['is_package'] ?? 0;

if ($is_package) {
    // Fetch items inside the package
    $pkg_stmt = sqlsrv_query($conn, "SELECT * FROM package_items WHERE package_id = ?", [$item_id]);
    while ($pkg_item = sqlsrv_fetch_array($pkg_stmt, SQLSRV_FETCH_ASSOC)) {
        $insert = sqlsrv_query($conn, "
            INSERT INTO delivery_queue (user_num, item_id, item_opt, quantity, package_id, status, created_at)
            VALUES (?, ?, ?, ?, ?, 0, GETDATE())",
            [
                $user_num,
                $pkg_item['item_id'],
                $pkg_item['item_opt'] ?? 0,
                $pkg_item['quantity'] * $quantity,
                $item_id
            ]
        );
    }
    echo json_encode(['success' => true, 'message' => 'Package queued successfully']);
} else {
    $insert = sqlsrv_query($conn, "
        INSERT INTO delivery_queue (user_num, item_id, item_opt, quantity, status, created_at)
        VALUES (?, ?, 0, ?, 0, GETDATE())",
        [$user_num, $item_id, $quantity]
    );

    if ($insert) {
        echo json_encode(['success' => true, 'message' => 'Item queued successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to queue item']);
    }
}
?>
 