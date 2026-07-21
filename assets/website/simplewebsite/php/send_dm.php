<?php
require_once(__DIR__ . '/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender = $_POST['sender'];
    $receiver = $_POST['receiver'];
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO direct_messages (sender, receiver, message) VALUES (:sender, :receiver, :message)");
        $stmt->bindParam(':sender', $sender);
        $stmt->bindParam(':receiver', $receiver);
        $stmt->bindParam(':message', $message);
        $stmt->execute();
    }
}
?>
