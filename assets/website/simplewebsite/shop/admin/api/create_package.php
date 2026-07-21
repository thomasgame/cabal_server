<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

    if (trim($name) === '') {
        die("Package name is required.");
    }

    $sql = "INSERT INTO packages (name, is_active) VALUES (?, ?)";
    $params = [$name, $is_active];

    $stmt = $connShop->prepare($sql);
    $success = $stmt->execute($params);

    if (!$success) {
        $errorInfo = $stmt->errorInfo();
        die("Error inserting package: " . $errorInfo[2]);
    }

    header("Location: ../../admin/admin_shop.php");
    exit;
} else {
    echo "Invalid request method.";
}
?>
