<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Invalid or missing item ID.");
}

try {
    $stmt = $connShop->prepare("DELETE FROM items WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: ../admin_shop.php");
    exit;
} catch (PDOException $e) {
    die("Error deleting item: " . $e->getMessage());
}
