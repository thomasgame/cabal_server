<?php
session_start();
require_once 'db.php';

// Only admin can toggle block status
$adminUsername = 'aerox009x';
if (!isset($_SESSION['user']) || $_SESSION['user'] !== $adminUsername) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userNum = intval($_POST['usernum']);
    $authType = intval($_POST['auth_type']); // 1 = unblock, 2 = block

    try {
        if ($authType === 1) {
            // Unblock using stored procedure
            $stmt = $conn->prepare("EXEC cabal_sp_Release_Block_User :UserNum");
            $stmt->bindParam(':UserNum', $userNum, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            // Just set AuthType = 2 to block
            $stmt = $conn->prepare("UPDATE cabal_auth_table SET AuthType = 2 WHERE UserNum = :UserNum");
            $stmt->bindParam(':UserNum', $userNum, PDO::PARAM_INT);
            $stmt->execute();
        }

        header("Location: manage_users.php");
        exit();
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>