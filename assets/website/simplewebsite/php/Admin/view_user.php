<?php
session_start();
require_once 'db.php'; // Ensure this includes both $connAccount and $connServer

// Show PHP errors for debugging (remove on production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get UserNum from query
$userNum = isset($_GET['usernum']) ? intval($_GET['usernum']) : 0;
if ($userNum === 0) {
    die("Invalid UserNum.");
}

try {
    // Get UserID from Account DB
    $stmtUser = $conn->prepare("SELECT ID FROM cabal_auth_table WHERE UserNum = ?");
$stmtUser->execute([$userNum]);
$userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
$userID = $userRow ? $userRow['ID'] : 'Unknown';


    // Get character list from Server DB
    $stmt = $connServer->prepare("EXEC get_cabal_character_list ?");
    $stmt->execute([$userNum]);
    $characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Characters</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <header class="bg-black p-4">
        <div class="max-w-7xl mx-auto flex justify-between">
        <h1 class="text-xl font-bold">Characters for UserNum <?= htmlspecialchars($userNum) ?> (<?= htmlspecialchars($userID) ?>)</h1>
            <a href="manage_users.php" class="text-yellow-400 hover:underline">Back to User List</a>
        </div>
    </header>
    <main class="max-w-6xl mx-auto p-6">
        <table class="w-full text-sm bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-700 text-yellow-300">
                <tr>
                    <th class="p-2 text-left">Character Name</th>
                    <th class="p-2 text-left">Level</th>
                    <th class="p-2 text-left">Nation</th>
                    <th class="p-2 text-left">Rank</th>
                    <th class="p-2 text-left">Reputation</th>
                    <th class="p-2 text-left">Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($characters) > 0): ?>
                    <?php foreach ($characters as $char): ?>
                        <tr class="border-t border-gray-700 hover:bg-gray-700">
                            <td class="p-2">
                                <a href="edit_character.php?name=<?= urlencode($char['Name']) ?>" class="text-yellow-400 hover:underline">
                                    <?= htmlspecialchars($char['Name']) ?>
                                </a>
                            </td>
                            <td class="p-2"><?= htmlspecialchars($char['LEV']) ?></td>
                            <td class="p-2"><?= htmlspecialchars($char['NATION']) ?></td>
                            <td class="p-2"><?= htmlspecialchars($char['RANK']) ?></td>
                            <td class="p-2"><?= htmlspecialchars($char['Reputation']) ?></td>
                            <td class="p-2"><?= htmlspecialchars($char['CreateDate']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="p-4 text-center text-gray-400">No characters found for this user.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
