<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Required fields
    $item_id = $_POST['item_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $subcategory_id = $_POST['subcategory_id'] ?? '';

    if (trim($item_id) === '' || trim($name) === '' || !$subcategory_id) {
        die("Item ID, Name, and Subcategory are required.");
    }

    // Optional fields
    $description = $_POST['description'] ?? '';
    $price = is_numeric($_POST['price']) ? $_POST['price'] : null;
    $item_opt = $_POST['item_opt'] ?? '';
    $is_package = isset($_POST['is_package']) ? (int)$_POST['is_package'] : 0;
    $expiration_date = $_POST['expiration_date'] ?? null;
    $purchase_limit = is_numeric($_POST['purchase_limit']) ? $_POST['purchase_limit'] : null;
    $limit_reset_period = $_POST['limit_reset_period'] ?? '';
    $package_id = null; // Default, can be extended

    $sql = "INSERT INTO items (
                item_id, subcategory_id, name, description, price,
                item_opt, is_package, expiration_date, purchase_limit,
                limit_reset_period, created_at, updated_at, package_id
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), GETDATE(), ?
            )";

    $stmt = $connShop->prepare($sql);

    $stmt->execute([
        $item_id,
        $subcategory_id,
        $name,
        $description,
        $price,
        $item_opt,
        $is_package,
        $expiration_date,     // Nullable
        $purchase_limit,
        $limit_reset_period,
        $package_id
    ]);

    header("Location: ../../admin/admin_shop.php");
    exit;
} else {
    echo "Invalid request method.";
}
?>
