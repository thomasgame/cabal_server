<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $package_id = $_POST['package_id'] ?? '';
    $item_id = $_POST['item_id'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    $item_opt = $_POST['item_opt'] ?? '';
    $name = $_POST['name'] ?? '';

    if (trim($package_id) === '' || trim($item_id) === '' || trim($quantity) === '' || trim($name) === '') {
        die("Package ID, Item ID, Quantity and Name are required.");
    }

    $sql = "INSERT INTO package_items (package_id, item_id, quantity, item_opt, name)
            VALUES (?, ?, ?, ?, ?)";
    $params = [$package_id, $item_id, $quantity, $item_opt, $name];

    // ?? Use PDO connection (connShop)
    $stmt = $connShop->prepare($sql);
    $success = $stmt->execute($params);

    if ($success) {
        header("Location: ../../admin/admin_shop.php");
        exit;
    } else {
        $error = $stmt->errorInfo();
        die("Error inserting package item: " . $error[2]);
    }
} else {
    echo "Invalid request method.";
}
