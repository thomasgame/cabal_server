<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

if (!isset($_GET['id'])) {
    die("No ID specified.");
}

$id = (int)$_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';

    if (trim($name) === '') {
        die("Name is required.");
    }

    $sql = "UPDATE main_categories SET name = :name, description = :description, updated_at = GETDATE() WHERE id = :id";
    $stmt = $connShop->prepare($sql);

    try {
        $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':id' => $id
        ]);
    } catch (PDOException $e) {
        die("Error updating main category: " . $e->getMessage());
    }

    header("Location: ../../admin/admin_shop.php");
    exit;
}

// Fetch current data
$sql = "SELECT * FROM main_categories WHERE id = :id";
$stmt = $connShop->prepare($sql);
$stmt->execute([':id' => $id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    die("Main category not found.");
}
?>

<!DOCTYPE html>
<html>
<head><title>Edit Main Category</title></head>
<body>
<h1>Edit Main Category</h1>
<form method="POST">
    <label>Name</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($category['name']) ?>" required><br><br>

    <label>Description</label><br>
    <textarea name="description"><?= htmlspecialchars($category['description']) ?></textarea><br><br>

    <button type="submit">Update</button>
    <a href="../../admin/admin_shop.php">Cancel</a>
</form>
</body>
</html>
