<?php
session_start();
require_once 'db.php'; // This must define $db

if (!isset($db) || !$db) {
    die("? Database connection not established.");
}

try {
    $query = "SELECT CharacterIdx, Name, Reputation, LEV, login FROM cabal_character_table ORDER BY LEV DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("? Database query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Character List - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(to bottom, #000000, #0b0b2b);
        }
    </style>
</head>

<body class="text-white font-sans overflow-x-hidden">
<main class="pt-24 px-4 max-w-screen-xl mx-auto flex gap-6">
    <section class="flex-1 bg-gray-900 p-6 rounded-lg shadow-lg text-white">
        <h1 class="text-2xl font-bold mb-4">Character List</h1>

        <table class="min-w-full table-auto">
            <thead>
                <tr class="bg-gray-700">
                    <th class="px-4 py-2 text-left">Character Name</th>
                    <th class="px-4 py-2 text-left">Reputation</th>
                    <th class="px-4 py-2 text-left">Level</th>
                    <th class="px-4 py-2 text-left">Online Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($characters)): ?>
                    <?php foreach ($characters as $character): ?>
                        <tr class="bg-gray-800 border-b">
                            <td class="px-4 py-2"><?php echo htmlspecialchars($character['Name']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($character['Reputation']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($character['LEV']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($character['login']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-center text-gray-500">No characters found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
