<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_POST['ip'] ?? null;
    $isBlocked = $_POST['is_blocked'] ?? null;

    if (!$ip || $isBlocked === null) {
        die("Missing IP or block status.");
    }

    // Unstun: Remove from block list
    if ($isBlocked == 1) {
        $sql = "DELETE FROM cabal_game_block_ip WHERE ip = :ip AND type = 2";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':ip', $ip);
        $stmt->execute();
    } else {
        // Stun: Add to block list if not already blocked
        $check = $conn->prepare("SELECT COUNT(*) FROM cabal_game_block_ip WHERE ip = :ip AND type = 2");
        $check->bindParam(':ip', $ip);
        $check->execute();
        $exists = $check->fetchColumn();

        if ($exists == 0) {
            $insert = $conn->prepare("INSERT INTO cabal_game_block_ip (ip, blocktime, type) VALUES (:ip, GETDATE(), 2)");
            $insert->bindParam(':ip', $ip);
            $insert->execute();
        }
    }

    header("Location: manage_users.php");
    exit();
}
