<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once '../config/db.php';
 // Make sure this file exists and works

if (!isset($_SESSION['usernum'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_num = intval($_SESSION['usernum']);

try {
    $stmt = $connShop->prepare("
        SELECT TOP 1000 
            i.name AS name,
            ph.quantity,
            ph.total_price AS price,
            ph.is_package,
            ph.purchase_date
        FROM purchase_history ph
        JOIN items i ON ph.item_id = i.id
        WHERE ph.user_num = ?
        ORDER BY ph.purchase_date DESC
    ");
    $stmt->execute([$user_num]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'history' => $results
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
