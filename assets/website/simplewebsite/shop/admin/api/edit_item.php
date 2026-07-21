<?php
require_once __DIR__ . '/../../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Missing item ID.");
}

// Fetch the item
$stmt = $connShop->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) {
    die("Item not found.");
}

// Fetch all main categories
$mainCats = $connShop->query("SELECT id, name FROM main_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch subcategories for the current main category (or all if none selected yet)
$currentMainCatId = null;
// We need to get the main category of this item’s subcategory
if ($item['subcategory_id']) {
    $stmt = $connShop->prepare("SELECT main_category_id FROM subcategories WHERE id = ?");
    $stmt->execute([$item['subcategory_id']]);
    $mainCat = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentMainCatId = $mainCat['main_category_id'] ?? null;
}

// Fetch subcategories for the current main category
$subCatsStmt = $connShop->prepare("SELECT id, name FROM subcategories WHERE main_category_id = ? ORDER BY name");
$subCatsStmt->execute([$currentMainCatId]);
$subCats = $subCatsStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Item</title>
    <script>
        // Optional: You can add JS here to dynamically load subcategories when main category changes (AJAX)
    </script>
</head>
<body>
    <h2>Edit Item</h2>
    <form action="update_item.php" method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">

        <label>Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" required><br>

        <label>Description:</label>
        <textarea name="description"><?= htmlspecialchars($item['description']) ?></textarea><br>

        <label>Price:</label>
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($item['price']) ?>" required><br>

        <label>Main Category:</label>
        <select name="main_category_id" required>
            <option value="">-- Select Main Category --</option>
            <?php foreach ($mainCats as $mc): ?>
                <option value="<?= $mc['id'] ?>" <?= $mc['id'] == $currentMainCatId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($mc['name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label>Subcategory:</label>
        <select name="subcategory_id" required>
            <option value="">-- Select Subcategory --</option>
            <?php foreach ($subCats as $sc): ?>
                <option value="<?= $sc['id'] ?>" <?= $sc['id'] == $item['subcategory_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sc['name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label>Is Package:</label>
        <input type="checkbox" name="is_package" value="1" <?= $item['is_package'] ? 'checked' : '' ?>><br>

        <!-- Add other fields as needed -->

        <button type="submit">Update Item</button>
    </form>
</body>
</html>
