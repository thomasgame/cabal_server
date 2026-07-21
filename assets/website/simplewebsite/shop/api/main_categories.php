<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../config/db.php');

try {
    $stmt = $connShop->query("SELECT id, name FROM main_categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'categories' => $categories]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
