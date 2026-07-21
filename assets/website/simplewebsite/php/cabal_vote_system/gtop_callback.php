<?php
require 'db.php';

if (!isset($_GET['username'])) {
    exit('No username specified.');
}

$username = $_GET['username'];
$ip = $_SERVER['REMOTE_ADDR'];

// 1. Check if user exists
$query = "SELECT UserNum FROM cabal_auth_table WHERE ID = ?";
$params = [$username];
$stmt = sqlsrv_query($conn, $query, $params);

if (!$stmt || !sqlsrv_fetch($stmt)) {
    exit('User not found.');
}

$userNum = sqlsrv_get_field($stmt, 0);

// 2. Check vote cooldown (12 hours)
$checkVote = "SELECT TOP 1 VoteTime FROM VoteLogs WHERE Username = ? ORDER BY VoteTime DESC";
$stmt2 = sqlsrv_query($conn, $checkVote, [$username]);

if ($stmt2 && sqlsrv_fetch($stmt2)) {
    $lastVote = sqlsrv_get_field($stmt2, 0);
    $now = new DateTime();
    $diff = $now->getTimestamp() - $lastVote->getTimestamp();

    if ($diff < 43200) {
        exit('You can only vote once every 12 hours.');
    }
}

// 3. Log the vote
$insertLog = "INSERT INTO VoteLogs (Username, IPAddress, VoteTime) VALUES (?, ?, GETDATE())";
sqlsrv_query($conn, $insertLog, [$username, $ip]);

// 4. Give 10 Cabal Cash
$checkCash = "SELECT * FROM cabal_charge_auth WHERE UserNum = ?";
$stmt3 = sqlsrv_query($conn, $checkCash, [$userNum]);

if (sqlsrv_fetch($stmt3)) {
    $updateCash = "UPDATE cabal_charge_auth SET Cash = Cash + 10 WHERE UserNum = ?";
    sqlsrv_query($conn, $updateCash, [$userNum]);
} else {
    $insertCash = "INSERT INTO cabal_charge_auth (UserNum, Cash) VALUES (?, 10)";
    sqlsrv_query($conn, $insertCash, [$userNum]);
}

echo 'Vote received. You earned 10 Cabal Cash!';
?>
