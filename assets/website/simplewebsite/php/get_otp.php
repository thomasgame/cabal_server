<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user'])) {
    die("Not logged in.");
}

$username = $_SESSION['user'];

try {
    $stmt = $conn->prepare("SELECT UserNum FROM cabal_auth_table WHERE ID = ?");
    $stmt->execute([$username]);
    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userRow || empty($userRow['UserNum'])) {
        die("User not found.");
    }

    $usernum = $userRow['UserNum'];

    $stmtOtp = $conn->prepare("SELECT externalid FROM cabal_otp_table WHERE usernum = ?");
    $stmtOtp->execute([$usernum]);
    $otpRow = $stmtOtp->fetch(PDO::FETCH_ASSOC);

    if (!$otpRow || empty($otpRow['externalid'])) {
        echo "No OTP yet.";
    } else {
        echo htmlspecialchars($otpRow['externalid']);
    }

} catch (PDOException $e) {
    echo "Database error.";
}
?>
