<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include DB connection
require_once 'db.php'; // Make sure the path is correct

try {
    // Query to get users
    $stmt = $conn->query("SELECT ID, login FROM [dbo].[cabal_auth_table]");

    $users = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users[] = [
            'id' => $row['ID'],
            'login' => $row['login']
        ];
    }

    // Return JSON
    header('Content-Type: application/json');
    echo json_encode($users);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
