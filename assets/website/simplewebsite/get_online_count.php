<?php
// Include your database connection
include 'php/db.php';

try {
    // Query the cabal_auth_table for accounts currently logged in
    $stmt = $conn->prepare("SELECT COUNT(*) AS online_count FROM account.dbo.cabal_auth_table WHERE Login = 1");
    $stmt->execute();

    // Fetch the result
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return JSON
    echo json_encode(['online_count' => $row['online_count']]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
