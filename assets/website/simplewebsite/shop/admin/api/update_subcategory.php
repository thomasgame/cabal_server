<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$id = $_POST['id'] ?? null;
$name = trim($_POST['name'] ?? '');
$desc = trim($_POST['description'] ?? '');
$main_id = $_POST['main_category_id'] ?? null;

if (!$id || $name === '' || !$main_id) {
    die("Missing required fields");
}

$sql = "UPDATE [dbo].[subcategories]
        SET name = ?, description = ?, main_category_id = ?, updated_at = GETDATE()
        WHERE id = ?";

try {
    $stmt = $connShop->prepare($sql);
    $success = $stmt->execute([$name, $desc, $main_id, $id]);

    if ($success) {
        header("Location: ../admin_shop.php");
        exit;
    } else {
        die("Failed to update subcategory");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
