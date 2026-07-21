<?php
require_once '../config/db.php'; // your database connection

$user_id = $_POST['user_id'];
$item_id = $_POST['item_id'];
$quantity = $_POST['quantity'] ?? 1;

// Step 1: Fetch the item
$item_stmt = sqlsrv_query($conn, "SELECT * FROM items WHERE id = ?", [$item_id]);
$item = sqlsrv_fetch_array($item_stmt, SQLSRV_FETCH_ASSOC);

if (!$item) {
    http_response_code(404);
    echo json_encode(["error" => "Item not found."]);
    exit;
}

$total_cost = $item['price'] * $quantity;

// ? Step 2: Fetch user's cash (from CabalCash DB)
$cash_stmt = sqlsrv_query($conn, "
    SELECT Cash, CashBonus 
    FROM CabalCash.dbo.CashAccount 
    WHERE UserNum = ?", [$user_id]);

$cashData = sqlsrv_fetch_array($cash_stmt, SQLSRV_FETCH_ASSOC);

if (!$cashData) {
    http_response_code(404);
    echo json_encode(["error" => "Cash account not found."]);
    exit;
}

$total_cash = $cashData['Cash'] + $cashData['CashBonus'];

if ($total_cash < $total_cost) {
    http_response_code(400);
    echo json_encode(["error" => "Insufficient funds."]);
    exit;
}

// ? Step 3: Deduct from cash
$cash_to_deduct = min($cashData['Cash'], $total_cost);
$bonus_to_deduct = $total_cost - $cash_to_deduct;

$update_stmt = sqlsrv_query($conn, "
    UPDATE CabalCash.dbo.CashAccount 
    SET Cash = Cash - ?, CashBonus = CashBonus - ? 
    WHERE UserNum = ?", [$cash_to_deduct, $bonus_to_deduct, $user_id]);

if (!$update_stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to deduct balance."]);
    exit;
}

// ? Step 4: Insert into purchase logs, queue, or send item here...
// TODO: add logic to insert item into delivery table or item queue

echo json_encode(["success" => true, "message" => "Purchase complete!"]);
?>
