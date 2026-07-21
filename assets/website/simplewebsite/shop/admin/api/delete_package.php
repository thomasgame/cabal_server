<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

$package_id = $_GET['id'] ?? null;

if (!$package_id || !is_numeric($package_id)) {
    die("Invalid or missing package ID.");
}

try {
    // Optional: delete related package_items first if there's a foreign key constraint
    $connShop->prepare("DELETE FROM package_items WHERE package_id = ?")->execute([$package_id]);

    // Now delete the package itself
    $stmt = $connShop->prepare("DELETE FROM packages WHERE package_id = ?");
    $stmt->execute([$package_id]);

    header("Location: ../admin_shop.php");
    exit;
} catch (PDOException $e) {
    die("Error deleting package: " . $e->getMessage());
}
