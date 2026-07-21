<?php
require_once(__DIR__ . '/../_connect/database.php'); 
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['reg_username']) ? trim($_POST['reg_username']) : '';
    $password = isset($_POST['reg_password']) ? trim($_POST['reg_password']) : '';
    
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Fields cannot be empty.']);
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        // 1. Check if ID exists
        $check = $db->prepare("SELECT COUNT(*) FROM Account.dbo.cabal_auth_table WHERE ID = ?");
        $check->execute([$username]);
        if ($check->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already taken.']);
            exit;
        }

        // 2. Execute the registration procedure provided by the Account database.
        $stmt = $db->prepare("
            EXEC Account.dbo.cabal_tool_registerAccount
                @id = ?,
                @password = ?,
                @email = '',
                @name = ?,
                @userkey = '',
                @refercode = ''
        ");
        $stmt->execute([$username, $password, $username]);
        
        // 3. THE FIX: Loop through rowsets to find the SELECT @UserNum
        $userNum = null;
        do {
            try {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result && (isset($result['usernum']) || isset($result['UserNum']))) {
                    $userNum = $result['usernum'] ?? $result['UserNum'];
                    break; // We found our data!
                }
            } catch (Exception $e) {
                // Skip empty rowsets created by INSERT/UPDATE
                continue;
            }
        } while ($stmt->nextRowset());

        $stmt->closeCursor();

        // 4. Verification
        if ($userNum) {
            echo json_encode(['success' => true, 'message' => 'Registration successful! ID: ' . $userNum]);
        } else {
            // If the loop finished but we didn't find UserNum, check if it was actually created
            $verify = $db->prepare("SELECT COUNT(*) FROM Account.dbo.cabal_auth_table WHERE ID = ?");
            $verify->execute([$username]);
            if ($verify->fetchColumn() > 0) {
                echo json_encode(['success' => true, 'message' => 'Account created successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Procedure executed but account not found.']);
            }
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request.']);
}