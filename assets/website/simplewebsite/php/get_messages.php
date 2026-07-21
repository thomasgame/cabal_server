<?php
require_once(__DIR__ . '/db.php');

// Get the current user's name (could be from session or URL)
$current_user = '<?= $username ?>';

$stmt = $conn->query("SELECT TOP 50 * FROM group_chat_messages ORDER BY timestamp DESC");
$messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

foreach ($messages as $msg) {
    $time = date('H:i', strtotime($msg['timestamp']));
    $messageText = htmlspecialchars($msg['message']);
    
    // Check if the message is a reply to someone else
    if ($msg['reply_to']) {
        $messageText = "<span class='text-yellow-400 font-semibold'>Replying to @" . htmlspecialchars($msg['reply_to']) . ":</span> " . $messageText;
    }
    
    // Check if the message mentions the current user
    if (strpos($messageText, '@' . $current_user) !== false) {
        // This is a mention, so display notification bell
        echo "<div class='mention' style='background-color: rgba(255, 255, 0, 0.2);'>";
    } else {
        echo "<div>";
    }

    echo "<span class='text-yellow-400 font-semibold'>" . htmlspecialchars($msg['username']) . "</span> ";
    echo "<span class='text-gray-400 text-xs'>[$time]</span>: ";
    echo $messageText . "</div>";
}
?>
