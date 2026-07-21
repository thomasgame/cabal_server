<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

$package_item_id = $_GET['id'] ?? null;

if (!$package_item_id || !is_numeric($package_item_id)) {
    die("Invalid or missing package item ID.");
}

try {
    $stmt = $connShop->prepare("DELETE FROM package_items WHERE id = ?");
    $stmt->execute([$package_item_id]);

    header("Location: ../admin_shop.php");
    exit;
} catch (PDOException $e) {
    die("Error deleting package item: " . $e->getMessage());
}
