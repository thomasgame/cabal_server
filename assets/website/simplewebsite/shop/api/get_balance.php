<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usernum'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

require_once '../config/db.php';

$user_num = $_SESSION['usernum'];

try {
    $stmt = $connCash->prepare("SELECT Cash, CashBonus FROM CashAccount WHERE UserNum = ?");
    $stmt->execute([$user_num]);
    $cash = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cash) {
        echo json_encode(['success' => false, 'error' => 'Cash account not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'balance' => [
            'Cash' => (int)$cash['Cash'],
            'CashBonus' => (int)$cash['CashBonus'],
            'Total' => (int)$cash['Cash'] + (int)$cash['CashBonus']
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB Error: ' . $e->getMessage()]);
}
