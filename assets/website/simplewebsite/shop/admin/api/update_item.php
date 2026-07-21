<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? '';
$desc = $_POST['description'] ?? '';
$price = $_POST['price'] ?? null;
$mainCatId = $_POST['main_category_id'] ?? null;
$subCatId = $_POST['subcategory_id'] ?? null;
$isPackage = isset($_POST['is_package']) ? 1 : 0;

if (!$id || !$name || !$price || !$mainCatId || !$subCatId) {
    die("Missing required fields");
}

// Optional: verify that subcategory belongs to main category
$stmt = $connShop->prepare("SELECT COUNT(*) FROM subcategories WHERE id = ? AND main_category_id = ?");
$stmt->execute([$subCatId, $mainCatId]);
if ($stmt->fetchColumn() == 0) {
    die("Subcategory does not belong to the selected main category.");
}

// Update item
$sql = "UPDATE items SET 
            name = ?, 
            description = ?, 
            price = ?, 
            subcategory_id = ?, 
            is_package = ?, 
            updated_at = GETDATE()
        WHERE id = ?";
$stmt = $connShop->prepare($sql);
$success = $stmt->execute([$name, $desc, $price, $subCatId, $isPackage, $id]);

if ($success) {
    header("Location: ../admin_shop.php");  // Adjust redirect as needed
    exit;
} else {
    die("Failed to update item");
}
