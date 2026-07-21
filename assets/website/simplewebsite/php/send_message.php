<?php
require_once(__DIR__ . '/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? 'Anonymous';
    $message = trim($_POST['message']);
    $replyTo = $_POST['reply_to'] ?? null;

    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO group_chat_messages (username, message, reply_to) VALUES (:username, :message, :reply_to)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':reply_to', $replyTo);
        $stmt->execute();
    }
}
?>
