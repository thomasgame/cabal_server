<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $main_category_id = $_POST['main_category_id'] ?? null;

    if (trim($name) === '' || !$main_category_id) {
        die("Name and main category are required.");
    }

    $sql = "INSERT INTO subcategories (name, description, main_category_id, created_at, updated_at)
            VALUES (?, ?, ?, GETDATE(), GETDATE())";
    $params = [$name, $description, $main_category_id];

    // Use the correct PDO connection — replace $conn with the correct one if needed
    $stmt = $connShop->prepare($sql);
    if ($stmt->execute($params)) {
        header("Location: ../../admin/admin_shop.php");
        exit;
    } else {
        $errorInfo = $stmt->errorInfo();
        die("Error inserting subcategory: " . $errorInfo[2]);
    }
} else {
    echo "Invalid request method.";
}
?>
