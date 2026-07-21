<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

// STEP 1: Input & Safety
$user_num = $_POST['user_num'] ?? null;
$selected_item_id = $_POST['item_id'] ?? null;
$quantity = max(1, intval($_POST['quantity'] ?? 1));

if (!isset($user_num, $selected_item_id) || !is_numeric($user_num) || !is_numeric($selected_item_id) || !is_numeric($quantity) || $quantity < 1) {
    echo json_encode(['success' => false, 'error' => 'Missing or invalid item_id/quantity/user_num']);
    exit;
}

// STEP 2: Fetch Item Info
$stmt = $connShop->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$selected_item_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo json_encode(['success' => false, 'error' => 'Item not found.']);
    exit;
}

$price = intval($item['price']);
$is_package = intval($item['is_package'] ?? 0);
$actual_item_id = intval($item['item_id'] ?? 0);  // Used if not a package
$package_id = $item['package_id'];
$total_cost = $price * $quantity;

// STEP 3: Get User Balance
$stmt = $connCash->prepare("SELECT Cash, CashBonus FROM CashAccount WHERE UserNum = ?");
$stmt->execute([$user_num]);
$cash = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cash) {
    echo json_encode(['success' => false, 'error' => 'Cash account not found.']);
    exit;
}

$total_cash = $cash['Cash'] + $cash['CashBonus'];
if ($total_cash < $total_cost) {
    echo json_encode(['success' => false, 'error' => 'Insufficient funds.']);
    exit;
}

// STEP 4: Deduct Funds
$deduct_cash = min($cash['Cash'], $total_cost);
$deduct_bonus = $total_cost - $deduct_cash;

$stmt = $connCash->prepare("
    UPDATE CashAccount
    SET Cash = Cash - ?, CashBonus = CashBonus - ?
    WHERE UserNum = ?");
$success = $stmt->execute([$deduct_cash, $deduct_bonus, $user_num]);

if (!$success) {
    echo json_encode(['success' => false, 'error' => 'Failed to deduct funds.']);
    exit;
}

// STEP 5: Send Items
try {
    $serverIdx = 1;
    $durationIdx = 0;
    $memo = 'WebShop';
    $totalSent = 0;

    if ($is_package && $package_id) {
        $pkg_stmt = $connShop->prepare("SELECT * FROM package_items WHERE package_id = ?");
	$pkg_stmt->execute([$package_id]);
        $items = $pkg_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            echo json_encode(['success' => false, 'error' => 'No items found in package.']);
            exit;
        }

        foreach ($items as $pkg_item) {
            $pkg_quantity = $pkg_item['quantity'] * $quantity;

            for ($i = 0; $i < $pkg_quantity; $i++) {
                $tranNo = time() + rand(0, 999999);

                $send_stmt = $connCash->prepare("EXEC dbo.up_AddMyCashItemByItem 
                    @UserNum = :usernum,
                    @TranNo = :tranno,
                    @ServerIdx = :serveridx,
                    @ItemIdx = :itemidx,
                    @ItemOpt = :itemopt,
                    @DurationIdx = :durationidx,
                    @Memo = :memo");

                $send_stmt->execute([
                    ':usernum'     => $user_num,
                    ':tranno'      => $tranNo,
                    ':serveridx'   => $serverIdx,
                    ':itemidx'     => $pkg_item['item_id'],
                    ':itemopt'     => $pkg_item['item_opt'] ?? 0,
                    ':durationidx' => $durationIdx,
                    ':memo'        => $memo
                ]);

                while ($send_stmt->nextRowset()) { /* flush result sets */ }

                $totalSent++;
            }
        }
    } else {
        for ($i = 0; $i < $quantity; $i++) {
            $tranNo = time() + rand(0, 999999);

            $send_stmt = $connCash->prepare("EXEC dbo.up_AddMyCashItemByItem 
                @UserNum = :usernum,
                @TranNo = :tranno,
                @ServerIdx = :serveridx,
                @ItemIdx = :itemidx,
                @ItemOpt = :itemopt,
                @DurationIdx = :durationidx,
                @Memo = :memo");

            $send_stmt->execute([
                ':usernum'     => $user_num,
                ':tranno'      => $tranNo,
                ':serveridx'   => $serverIdx,
                ':itemidx'     => $actual_item_id,
                ':itemopt'     => $item['item_opt'] ?? 0,
                ':durationidx' => $durationIdx,
                ':memo'        => $memo
            ]);

            while ($send_stmt->nextRowset()) { /* flush result sets */ }

            $totalSent++;
        }
    }

    // STEP 6: Log purchase
    $history_stmt = $connShop->prepare("INSERT INTO purchase_history (user_num, item_id, quantity, total_price, is_package, purchase_date)
        VALUES (?, ?, ?, ?, ?, GETDATE())");
    $history_stmt->execute([$user_num, $selected_item_id, $quantity, $total_cost, $is_package]);

    echo json_encode(['success' => true, 'message' => "Item(s) sent: $totalSent"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to deliver item(s): ' . $e->getMessage()]);
}
