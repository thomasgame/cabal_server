<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

if (!isset($_GET['id'])) {
    die("No ID specified.");
}

$id = (int)$_GET['id'];

$sql = "DELETE FROM main_categories WHERE id = :id";
$stmt = $connShop->prepare($sql);
try {
    $stmt->execute(['id' => $id]);
} catch (PDOException $e) {
    die("Error deleting main category: " . $e->getMessage());
}

header("Location: ../../admin/admin_shop.php");
exit;
?>
