<?php
session_start();
require_once(__DIR__ . '/../php/db.php'); // Assumes $conn is a PDO instance

// Ensure the user is logged in
$userID = $_SESSION['user'] ?? '';
$userNum = $_SESSION['usernum'] ?? '';
if (!$userID || !$userNum) {
    die("You must be logged in to vote.");
}

// Define the current timestamp
$now = new DateTime();

// Define cooldown period in minutes
$cooldownMinutes = 720; // 12 hours

// Retrieve user information
$ip = $_SERVER['REMOTE_ADDR'];
$voteSite = $_POST['site'] ?? 'gtop100';
$hwid = $_POST['hwid'] ?? ''; // HWID should be sent from the client-side

// Define available voting sites
$voteSites = [
    'gtop100' => 'https://gtop100.com/cabal-online-private-servers/GLOBAL-CABALEP3335-102916?vote=1',
    'xtremetop100' => 'https://www.xtremetop100.com/in.php?site=1132375906',
    // Add more sites as needed
];

// Validate selected vote site
if (!array_key_exists($voteSite, $voteSites)) {
    die("Invalid vote site.");
}

// Check if the user/IP/HWID has voted within the cooldown period
$sql = "SELECT TOP 1 VoteTime FROM VoteLogs 
        WHERE (UserID = :userID OR IPAddress = :ip OR HWID = :hwid) 
        AND VoteSite = :voteSite 
        ORDER BY VoteTime DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([
    'userID' => $userID,
    'ip' => $ip,
    'hwid' => $hwid,
    'voteSite' => $voteSite
]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $lastVote = new DateTime($row['VoteTime']);
    $nextVoteTime = clone $lastVote;
    $nextVoteTime->modify("+{$cooldownMinutes} minutes");

    if ($nextVoteTime > $now) {
        $waitTime = $now->diff($nextVoteTime);
        die("You must wait {$waitTime->h} hours and {$waitTime->i} minutes before voting again.");
    }
}

// Insert vote log
$nextVote = clone $now;
$nextVote->modify("+{$cooldownMinutes} minutes");

$insert = "INSERT INTO VoteLogs (UserID, IPAddress, HWID, VoteSite, VoteTime, NextVoteTime)
           VALUES (:userID, :ip, :hwid, :voteSite, :voteTime, :nextVoteTime)";
$stmt = $conn->prepare($insert);
$stmt->execute([
    'userID' => $userID,
    'ip' => $ip,
    'hwid' => $hwid,
    'voteSite' => $voteSite,
    'voteTime' => $now->format('Y-m-d H:i:s'),
    'nextVoteTime' => $nextVote->format('Y-m-d H:i:s')
]);

// Insert reward into RewardQueue
$rewardSql = "INSERT INTO RewardQueue (UserID, UserNum, RewardType, Amount, AddedAt)
              VALUES (:userID, :userNum, 'eCoins', 100, :addedAt)";
$rewardStmt = $conn->prepare($rewardSql);
$rewardStmt->execute([
    'userID' => $userID,
    'userNum' => $userNum,
    'addedAt' => $now->format('Y-m-d H:i:s')
]);

// Define the amount to add
$amountToAdd = 150; // Adjust this value as needed

try {
    // Set error mode to exceptions
    $connCash->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare the UPDATE statement
    $updateSql = "UPDATE [CabalCash].[dbo].[CashAccount]
                  SET Cash = Cash + :amount
                  WHERE UserNum = :userNum";
    $updateStmt = $connCash->prepare($updateSql);

    // Execute the UPDATE statement with bound parameters
    $updateStmt->execute([
        'amount' => $amountToAdd,
        'userNum' => $userNum
    ]);

   // Display success popup message
    echo "<script>alert('Cash balance updated successfully.');</script>";
} catch (PDOException $e) {
    // Handle any errors and display error popup message
    error_log("Error updating cash balance: " . $e->getMessage());
    echo "<script>alert('An error occurred while updating cash balance.');</script>";
}

// Redirect to the selected vote site
header("Location: " . $voteSites[$voteSite]);
exit;
?>
