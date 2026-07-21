<?php
require_once('_connect/database.php');
header('Content-Type: application/json');
session_start();

$conn = new Database;
$db = $conn->getConnection();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    if (strlen($username) < 4 || strlen($password) < 4) {
        echo json_encode(['success' => false, 'message' => 'Username and password must be at least 4 characters.']);
        exit;
    }

    try {
        $statusStmt = $db->prepare("SELECT AuthType FROM Account.dbo.cabal_auth_table WHERE ID = ?");
        $statusStmt->execute([$username]);
        $authType = $statusStmt->fetchColumn();

        if ($authType !== false && $authType > 1) {
            echo json_encode(['success' => false, 'message' => 'Account is banned from this server!']);
            exit;
        }

        $stmt = $db->prepare("
            SELECT ID FROM Account.dbo.cabal_auth_table
            WHERE ID = ? AND PWDCOMPARE(?, Password) = 1
        ");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['username'] = $user['ID'];
            echo json_encode(['success' => true, 'message' => 'Login successful!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Login failed due to server error.']);
    }
}
?>
