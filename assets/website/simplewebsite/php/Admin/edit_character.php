<?php
session_start();
require_once 'db.php'; // assumes $connServer is defined here


$originalCharName = $_GET['name'] ?? '';
if (!$originalCharName) {
    die("No character selected.");
}

$char = [];

try {
    // Fetch character info
    $stmt = $connServer->prepare("
        SELECT CharacterIdx, name, lev, exp, str, dex, int, pnt, alz, style, reputation, nation 
        FROM cabal_character_table 
        WHERE name = ?
    ");
    $stmt->execute([$originalCharName]);
    $char = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$char) {
        die("Character not found.");
    }

    // Restore skill from backup
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_skill'])) {
        $restoreStmt = $connServer->prepare("
            UPDATE cabal_skilllist_table
            SET Data = (
                SELECT SkillData FROM cabal_skilllist_backup WHERE CharacterIdx = ?
            )
            WHERE CharacterIdx = ?
        ");
        $restoreStmt->execute([$_POST['CharacterIdx'], $_POST['CharacterIdx']]);
        $message = "Skills restored from backup!";
    }

    // Remove GM skills
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_skill'])) {
        $removeStmt = $connServer->prepare("
            UPDATE cabal_skilllist_table
            SET Data = CAST(REPLACE(Data, 
                CAST(0xCC01012300CD01012400CE01012500CF0101260090000127009100012800920001290093000130004B02013100B502013200 AS VARBINARY(MAX)), 
                0x
            ) AS VARBINARY(MAX))
            WHERE CharacterIdx = ?
        ");
        $removeStmt->execute([$_POST['CharacterIdx']]);
        $message = "GM skills removed successfully!";
    }

    // Add GM skills with backup
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['skill_update'])) {
        $charId = $_POST['CharacterIdx'];

        // Check if backup exists
        $checkBackup = $connServer->prepare("SELECT 1 FROM cabal_skilllist_backup WHERE CharacterIdx = ?");
        $checkBackup->execute([$charId]);
        $hasBackup = $checkBackup->fetchColumn();

        // If not, back up
        if (!$hasBackup) {
            $backupStmt = $connServer->prepare("
                INSERT INTO cabal_skilllist_backup (CharacterIdx, SkillData)
                SELECT CharacterIdx, Data FROM cabal_skilllist_table WHERE CharacterIdx = ?
            ");
            $backupStmt->execute([$charId]);
        }

        // Add GM skills
        $skillUpdate = $connServer->prepare("
            UPDATE cabal_skilllist_table
            SET Data = Data + CAST(
                0xCC01012300CD01012400CE01012500CF0101260090000127009100012800920001290093000130004B02013100B502013200 
                AS VARBINARY(MAX)
            )
            WHERE CharacterIdx = ?
        ");
        $skillUpdate->execute([$charId]);

        $message = "GM skills added successfully. Backup created if not already.";
    }

    // Update character info
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['skill_update']) && !isset($_POST['remove_skill']) && !isset($_POST['restore_skill'])) {
        $updateStmt = $connServer->prepare("
            UPDATE cabal_character_table 
            SET name = ?, lev = ?, exp = ?, str = ?, dex = ?, int = ?, pnt = ?, alz = ?, style = ?, reputation = ?, nation = ?
            WHERE CharacterIdx = ?
        ");
        $updateStmt->execute([
            $_POST['name'], $_POST['lev'], $_POST['exp'], $_POST['str'], $_POST['dex'], $_POST['int'], $_POST['pnt'],
            $_POST['alz'], $_POST['style'], $_POST['reputation'], $_POST['nation'], $_POST['CharacterIdx']
        ]);

        // Refresh updated data
        $originalCharName = $_POST['name'];
        $stmt->execute([$originalCharName]);
        $char = $stmt->fetch(PDO::FETCH_ASSOC);

        $message = "Character updated successfully!";
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Character: <?= htmlspecialchars($char['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <div class="max-w-2xl mx-auto mt-10 bg-gray-800 p-6 rounded-lg shadow-lg">
        <h1 class="text-2xl font-bold text-yellow-400 mb-6">Editing: <?= htmlspecialchars($char['name']) ?></h1>

        <?php if (isset($message)): ?>
            <p class="mb-4 text-green-400"><?= $message ?></p>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <input type="hidden" name="CharacterIdx" value="<?= htmlspecialchars($char['CharacterIdx']) ?>">

            <?php foreach ($char as $key => $val): ?>
    <?php if ($key === 'CharacterIdx') continue; ?>
    <div>
        <label class="block font-medium capitalize mb-1"><?= $key ?>:</label>

        <?php if ($key === 'nation'): ?>
            <select name="nation" class="w-full p-2 bg-gray-700 border border-gray-600 rounded text-white">
                <option value="0" <?= $val == 0 ? 'selected' : '' ?>>Neutral</option>
                <option value="1" <?= $val == 1 ? 'selected' : '' ?>>Capella</option>
                <option value="2" <?= $val == 2 ? 'selected' : '' ?>>Procyon</option>
                <option value="3" <?= $val == 3 ? 'selected' : '' ?>>GM</option>
            </select>
        <?php else: ?>
            <input type="text" name="<?= $key ?>" value="<?= htmlspecialchars($val) ?>" class="w-full p-2 bg-gray-700 border border-gray-600 rounded text-white">
        <?php endif; ?>
    </div>
<?php endforeach; ?>

            <div class="mt-6 flex justify-between items-center">
                <a href="manage_users.php" class="text-yellow-300 hover:underline">&larr; Back to User List</a>
                <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 text-black px-4 py-2 rounded font-semibold">
                    Save
                </button>
            </div>
        </form>

         <?php if ($char['nation'] == 3): ?>
    <!-- Add GM Skills -->
    <form method="post" class="mt-6">
        <input type="hidden" name="CharacterIdx" value="<?= htmlspecialchars($char['CharacterIdx']) ?>">
        <input type="hidden" name="skill_update" value="1">
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold">
            Add GM Skills
        </button>
    </form>

    <!-- Remove GM Skills -->
    <form method="post" class="mt-4">
        <input type="hidden" name="CharacterIdx" value="<?= htmlspecialchars($char['CharacterIdx']) ?>">
        <input type="hidden" name="remove_skill" value="1">
        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded font-semibold">
            Remove GM Skills
        </button>
    </form>

    <!-- Restore Skill Backup -->
    <form method="post" class="mt-4">
        <input type="hidden" name="CharacterIdx" value="<?= htmlspecialchars($char['CharacterIdx']) ?>">
        <input type="hidden" name="restore_skill" value="1">
        <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded font-semibold">
            Restore Skill Backup
        </button>
    </form>
<?php endif; ?>
    </div>
</body>
</html>