<?php
require_once(__DIR__ . '/db.php');

// Ensure 'receiver' parameter is passed
$sender = '<?= $username ?>'; // Get the current logged-in user's username
$receiver = isset($_GET['receiver']) ? $_GET['receiver'] : null;

// Debugging: Output the received value of 'receiver'
if (!$receiver) {
    echo "Error: Receiver parameter is missing.";
    die();  // Exit the script after the error message
}

// Prepare and execute the SQL query
$stmt = $conn->prepare("SELECT * FROM direct_messages WHERE (sender = :sender AND receiver = :receiver) OR (sender = :receiver AND receiver = :sender) ORDER BY timestamp DESC");
$stmt->bindParam(':sender', $sender);
$stmt->bindParam(':receiver', $receiver);
$stmt->execute();

$messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

foreach ($messages as $msg) {
    $time = date('H:i', strtotime($msg['timestamp']));
    $messageText = htmlspecialchars($msg['message']);  // Ensure no null values
    $senderName = htmlspecialchars($msg['sender']);  // Sanitize sender

    echo "<div><span class='text-yellow-400 font-semibold'>$senderName</span> ";
    echo "<span class='text-gray-400 text-xs'>[$time]</span>: ";
    echo $messageText . "</div>";
}
?>
