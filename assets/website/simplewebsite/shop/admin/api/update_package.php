<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

    if (!$id || trim($name) === '') {
        die("Missing fields.");
    }

    $sql = "UPDATE packages SET name = ?, is_active = ? WHERE package_id = ?";
    $stmt = $connShop->prepare($sql);
    $success = $stmt->execute([$name, $is_active, $id]);

    if ($success) {
        header("Location: ../../admin/admin_shop.php");
        exit;
    } else {
        $error = $stmt->errorInfo();
        die("Error updating package: " . $error[2]);
    }
} else {
    echo "Invalid request method.";
}
