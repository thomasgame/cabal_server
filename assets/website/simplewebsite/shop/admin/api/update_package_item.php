<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? '';
$item_id = $_POST['item_id'] ?? null;
$quantity = $_POST['quantity'] ?? 1;
$item_opt = $_POST['item_opt'] ?? '';
$package_id = $_POST['package_id'] ?? null;

if (!$id || !$name || !$item_id || !$package_id) {
    die("Missing required fields");
}

$sql = "UPDATE package_items 
        SET name = ?, item_id = ?, quantity = ?, item_opt = ?, package_id = ?
        WHERE id = ?";
$stmt = $connShop->prepare($sql);
$success = $stmt->execute([$name, $item_id, $quantity, $item_opt, $package_id, $id]);

if ($success) {
    header("Location: ../admin_shop.php");
    exit;
} else {
    die("Failed to update package item");
}
