<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $desc = $_POST['description'];

    $sql = "INSERT INTO main_categories (name, description, created_at, updated_at)
            VALUES (?, ?, GETDATE(), GETDATE())";

    $stmt = $connShop->prepare($sql);
    if ($stmt->execute([$name, $desc])) {
        header("Location: ../../admin/admin_shop.php");
        exit();
    } else {
        $errorInfo = $stmt->errorInfo();
        die("Insert failed: " . $errorInfo[2]);
    }
}
?>