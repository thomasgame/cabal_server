<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Missing package ID.");
}

// Fetch the package by ID
$stmt = $connShop->prepare("SELECT * FROM packages WHERE package_id = ?");
$stmt->execute([$id]);
$package = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$package) {
    die("Package not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Package</title>
</head>
<body>
    <h2>Edit Package</h2>
    <form action="update_package.php" method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($package['package_id']) ?>">

        <label>Package Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($package['name'] ?? '') ?>" required><br>

        <label>Is Active:</label>
        <select name="is_active">
            <option value="1" <?= $package['is_active'] ? 'selected' : '' ?>>Yes</option>
            <option value="0" <?= !$package['is_active'] ? 'selected' : '' ?>>No</option>
        </select><br>

        <button type="submit">Update Package</button>
    </form>
</body>
</html>
