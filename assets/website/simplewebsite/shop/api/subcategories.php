<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../config/db.php');

if (!isset($_GET['main_category_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing main_category_id']);
    exit;
}

$main_category_id = intval($_GET['main_category_id']);

try {
    $stmt = $conn->prepare("SELECT id, name FROM CabalShop.dbo.subcategories WHERE main_category_id = ?");
    $stmt->execute([$main_category_id]);
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'subcategories' => $subcategories]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
