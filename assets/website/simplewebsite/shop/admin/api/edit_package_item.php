<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Missing package item ID.");
}

// Fetch the package item
$stmt = $connShop->prepare("SELECT * FROM package_items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) {
    die("Package item not found.");
}

// Fetch all packages
$pkgStmt = $connShop->query("SELECT package_id, name FROM packages ORDER BY name");
$packages = $pkgStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Package Item</title>
</head>
<body>
    <h2>Edit Package Item</h2>
    <form action="update_package_item.php" method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">

        <label>Item Name:</label>
<input type="text" name="name" value="<?= htmlspecialchars($item['name'] ?? '') ?>" required><br>

<label>Item ID:</label>
<input type="number" name="item_id" value="<?= htmlspecialchars($item['item_id'] ?? '') ?>" required><br>

<label>Quantity:</label>
<input type="number" name="quantity" value="<?= htmlspecialchars($item['quantity'] ?? 1) ?>" required><br>

<label>Item Option:</label>
<input type="text" name="item_opt" value="<?= htmlspecialchars($item['item_opt'] ?? '') ?>"><br>

<label>Package:</label>
<select name="package_id" required>
    <option value="">-- Select Package --</option>
    <?php foreach ($packages as $pkg): ?>
        <option value="<?= $pkg['package_id'] ?>" <?= $pkg['package_id'] == $item['package_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($pkg['name']) ?>
        </option>
    <?php endforeach; ?>
</select><br>
        <button type="submit">Update</button>
    </form>
</body>
</html>
