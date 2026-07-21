<?php
require_once '_connect/database.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }
    
    if (strlen($username) < 4 || strlen($password) < 4) {
        echo json_encode(['success' => false, 'message' => 'Username and password must be at least 4 characters long.']);
        exit;
    }

    $db = new database();
    $conn = $db->getConnection();

    try {
        // Check if username already exists
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Account.dbo.cabal_auth_table WHERE ID = ?");
        $checkStmt->execute([$username]);
        $exists = $checkStmt->fetchColumn();

        if ($exists > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already exists.']);
            exit;
        }

        // Proceed with the registration procedure provided by the Account database.
        $stmt = $conn->prepare("
            EXEC Account.dbo.cabal_tool_registerAccount
                @id = ?,
                @password = ?,
                @email = '',
                @name = ?,
                @userkey = '',
                @refercode = ''
        ");
        $stmt->execute([$username, $password, $username]);

        echo json_encode(['success' => true, 'message' => 'Registration successful!']);
    } catch (PDOException $e) {
        error_log("SQL error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Registration failed.']);
    }
}
?>
