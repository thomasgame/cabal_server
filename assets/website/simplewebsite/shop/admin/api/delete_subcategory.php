<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

if (!isset($_GET['id'])) {
    die("Missing subcategory ID.");
}

$id = intval($_GET['id']); // Basic sanitization

try {
    $stmt = $connShop->prepare("DELETE FROM subcategories WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: ../../admin/admin_shop.php");
        exit;
    } else {
        $errorInfo = $stmt->errorInfo();
        die("Error deleting subcategory: " . $errorInfo[2]);
    }
} catch (PDOException $e) {
    die("Exception: " . $e->getMessage());
}
?>
