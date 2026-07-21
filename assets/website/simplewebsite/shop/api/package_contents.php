<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$itemId = $_GET['item_id'] ?? null;

if (!$itemId) {
    echo json_encode(["success" => false, "error" => "Missing item_id"]);
    exit;
}

try {
    $stmt = $connShop->prepare("
        SELECT id, item_id, name, description, price, is_package, package_id
        FROM CabalShop.dbo.items
        WHERE id = ?
    ");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(["success" => false, "error" => "Item not found"]);
        exit;
    }

    $response = ["success" => true, "item" => $item];

    if ((int)$item['is_package'] === 1 && !empty($item['package_id'])) {
    $packageId = $item['package_id'];

    $stmt2 = $connShop->prepare("
    SELECT pi.name AS item_name, pi.quantity, pi.item_id
    FROM CabalShop.dbo.package_items pi
    WHERE pi.package_id = ?
");
    $stmt2->execute([$packageId]);
    $packageItems = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    if (!$packageItems) {
        error_log("Package $packageId has no items or failed join.");
    }

    $response['package_items'] = $packageItems;
}
    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
