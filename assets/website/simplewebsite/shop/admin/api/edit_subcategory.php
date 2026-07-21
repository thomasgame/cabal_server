<?php
require_once __DIR__ . '/../../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Missing subcategory ID.");
}

$stmt = $connShop->prepare("SELECT * FROM subcategories WHERE id = ?");
$stmt->execute([$id]);
$sub = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sub) {
    die("Subcategory not found.");
}

// Fetch main categories for the dropdown
$mainStmt = $connShop->query("SELECT id, name FROM main_categories");
$mainCategories = $mainStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head><title>Edit Subcategory</title></head>
<body>
    <h2>Edit Subcategory</h2>
    <form action="./update_subcategory.php" method="POST">
        <input type="hidden" name="id" value="<?= $sub['id'] ?>">
        <label>Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($sub['name']) ?>" required><br>
        
        <label>Description:</label>
        <textarea name="description"><?= htmlspecialchars($sub['description']) ?></textarea><br>
        
        <label>Main Category:</label>
        <select name="main_category_id" required>
            <?php foreach ($mainCategories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $sub['main_category_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <button type="submit">Update</button>
    </form>
</body>
</html>
