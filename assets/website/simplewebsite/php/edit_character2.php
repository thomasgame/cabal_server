<?php
session_start();
require_once 'db.php';

// ======================
// CONFIG
// ======================
$charName = $_GET['name'] ?? '';
if (!$charName) die("No character selected.");

$costPerEnergy = 100000;   // Alz per 1 energy
$maxEnergy = 100000;    // Maximum Making Energy

$nationMap = [
    1 => 'Capella',
    2 => 'Procyon',
    3 => 'Game Master'
];

try {
    /* ===============================
       LOAD CHARACTER
    =============================== */
    $stmt = $connServer->prepare("
        SELECT CharacterIdx, lev, exp, str, dex, int, pnt, alz, style, reputation, nation
        FROM cabal_character_table
        WHERE name = ?
    ");
    $stmt->execute([$charName]);
    $char = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$char) die("Character not found.");

    $characterIdx = (int)$char['CharacterIdx'];

    /* ===============================
       LOAD MAKING ENERGY
    =============================== */
    $energyStmt = $connServer->prepare("
        SELECT MakingEnergy
        FROM cabal_craft_renewal_table
        WHERE CharacterIdx = ?
    ");
    $energyStmt->execute([$characterIdx]);
    $energyRow = $energyStmt->fetch(PDO::FETCH_ASSOC);
    $makingEnergy = (int)($energyRow['MakingEnergy'] ?? 0);

    /* ===============================
       POST HANDLER
    =============================== */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        /* ===== MAKING ENERGY RECHARGE ===== */
        if (($_POST['action'] ?? '') === 'recharge_energy') {

            $addEnergy = max(0, (int)($_POST['energy_amount'] ?? 0));

            if ($addEnergy <= 0) {
                die("Invalid energy amount.");
            }

            // Prevent overfilling above maxEnergy
            $availableToAdd = $maxEnergy - $makingEnergy;
            if ($availableToAdd <= 0) {
                echo "<script>alert('Making Energy is already at maximum ($maxEnergy)!'); 
                      window.location.href='edit_character2.php?name=$charName';</script>";
                exit;
            }

            // Cap addEnergy to the available space
            $addEnergy = min($addEnergy, $availableToAdd);

            $totalCost = $addEnergy * $costPerEnergy;

            if ($char['alz'] < $totalCost) {
                echo "<script>alert('Not enough Alz. Required: $totalCost'); 
                      window.location.href='edit_character2.php?name=$charName';</script>";
                exit;
            }

            // Deduct Alz
            $updateAlz = $connServer->prepare("
                UPDATE cabal_character_table
                SET alz = alz - ?
                WHERE CharacterIdx = ?
            ");
            $updateAlz->execute([$totalCost, $characterIdx]);

            // Check if record exists
            $check = $connServer->prepare("
                SELECT CharacterIdx
                FROM cabal_craft_renewal_table
                WHERE CharacterIdx = ?
            ");
            $check->execute([$characterIdx]);

            if ($check->fetch()) {
                // UPDATE existing
                $upd = $connServer->prepare("
                    UPDATE cabal_craft_renewal_table
                    SET MakingEnergy = MakingEnergy + ?,
                        MakingEnergyChargeTime = DATEDIFF(SECOND, '1970-01-01', GETDATE())
                    WHERE CharacterIdx = ?
                ");
                $upd->execute([$addEnergy, $characterIdx]);
            } else {
                // INSERT new row
                $ins = $connServer->prepare("
                    INSERT INTO cabal_craft_renewal_table
                    (
                        CharacterIdx,
                        RequestExp,
                        RequestRecipeBitField,
                        RequestData,
                        MakingData,
                        MakingEnergy,
                        MakingEnergyChargeTime,
                        Bookmark,
                        BookmarkBitField
                    )
                    VALUES
                    (
                        ?, 0, 0x0, 0x0, 0x0, ?, DATEDIFF(SECOND, '1970-01-01', GETDATE()), 0, 0x0
                    )
                ");
                $ins->execute([$characterIdx, $addEnergy]);
            }

            echo "<script>alert('Making Energy successfully recharged! Added $addEnergy energy. Alz deducted: $totalCost'); 
                  window.location.href='edit_character2.php?name=$charName';</script>";
            exit;
        }

        /* ===== STAT UPDATE ===== */
        $updatedStr = (int)($_POST['str'] ?? $char['str']);
        $updatedDex = (int)($_POST['dex'] ?? $char['dex']);
        $updatedInt = (int)($_POST['int'] ?? $char['int']);

        $usedPnt =
            max(0, $updatedStr - $char['str']) +
            max(0, $updatedDex - $char['dex']) +
            max(0, $updatedInt - $char['int']);

        if ($usedPnt > $char['pnt']) {
            echo "<script>alert('Not enough stat points.');
                  window.location.href='edit_character2.php?name=$charName';</script>";
            exit;
        }

        $newPnt = $char['pnt'] - $usedPnt;

        $updateStmt = $connServer->prepare("
            UPDATE cabal_character_table
            SET str = ?, dex = ?, int = ?, pnt = ?
            WHERE name = ?
        ");
        $updateStmt->execute([$updatedStr, $updatedDex, $updatedInt, $newPnt, $charName]);

        echo "<script>alert('Character updated successfully!');
              window.location.href='edit_character2.php?name=$charName';</script>";
        exit;
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Character: <?= htmlspecialchars($charName) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const energyInput = document.querySelector('input[name="energy_amount"]');
    const alzCost = <?= $costPerEnergy ?>;
    const charAlz = <?= $char['alz'] ?>;
    const maxEnergy = <?= $maxEnergy ?>;
    const currentEnergy = <?= $makingEnergy ?>;

    const alzDisplay = document.createElement('div');
    alzDisplay.className = "text-yellow-300 mb-2";
    energyInput.parentNode.insertBefore(alzDisplay, energyInput.nextSibling);

    function updateAlzCost() {
        let amount = parseInt(energyInput.value) || 0;
        // Cap to maxEnergy
        const availableToAdd = maxEnergy - currentEnergy;
        amount = Math.min(amount, availableToAdd);
        const total = amount * alzCost;
        alzDisplay.textContent = `Total Alz Cost: ${total} (Available: ${charAlz})`;
        energyInput.setCustomValidity(total > charAlz ? "Not enough Alz" : "");
    }

    energyInput.addEventListener('input', updateAlzCost);
    updateAlzCost();
});
</script>
</head>

<body class="bg-gray-900 text-white">
<div class="max-w-xl mx-auto mt-10 bg-gray-800 p-6 rounded-lg shadow-lg">

<h1 class="text-2xl font-bold text-yellow-400 mb-6">
    Editing: <?= htmlspecialchars($charName) ?>
</h1>

<!-- CURRENT MAKING ENERGY -->
<div class="mb-4">
    <label class="block font-medium mb-1">Current Making Energy:</label>
    <input type="text"
           value="<?= $makingEnergy ?>"
           class="w-full p-2 bg-gray-600 border border-gray-500 rounded text-white"
           readonly>
</div>

<hr class="my-6 border-gray-600">

<!-- RECHARGE FORM -->
<h2 class="text-xl font-bold text-cyan-400 mb-3">Recharge Making Energy</h2>

<form method="post" class="space-y-3">
    <input type="hidden" name="action" value="recharge_energy">

    <input type="number"
           name="energy_amount"
           min="1"
           max="<?= $maxEnergy ?>"
           required
           class="w-full p-2 bg-gray-700 border border-gray-600 rounded"
           placeholder="Energy amount">

    <button class="bg-cyan-400 hover:bg-cyan-500 text-black px-4 py-2 rounded font-semibold w-full">
        Recharge Energy
    </button>
</form>

<hr class="my-6 border-gray-600">

<!-- STAT EDIT -->
<form method="post" class="space-y-4" id="statForm">
<?php foreach ($char as $key => $val): ?>

<?php if (in_array($key, ['str','dex','int'])): ?>
<div>
<label class="block font-medium mb-1"><?= strtoupper($key) ?>:</label>
<input type="number" name="<?= $key ?>"
       id="input-<?= $key ?>"
       value="<?= (int)$val ?>"
       class="w-full p-2 bg-gray-700 border border-gray-600 rounded stat-field">
</div>

<?php elseif ($key === 'pnt'): ?>
<div>
<label class="block font-medium mb-1">Available Points:</label>
<input type="number" id="pntDisplay"
       value="<?= (int)$val ?>"
       class="w-full p-2 bg-gray-600 border border-gray-500 rounded"
       readonly>
</div>

<?php elseif (in_array($key,['lev','alz','reputation'])): ?>
<div>
<label class="block font-medium mb-1"><?= ucfirst($key) ?>:</label>
<input type="text" value="<?= $val ?>"
       class="w-full p-2 bg-gray-600 border border-gray-500 rounded"
       readonly>
</div>

<?php elseif ($key === 'nation'): ?>
<div>
<label class="block font-medium mb-1">Nation:</label>
<input type="text"
       value="<?= $nationMap[$val] ?? 'Unknown' ?>"
       class="w-full p-2 bg-gray-600 border border-gray-500 rounded"
       readonly>
</div>

<?php endif; ?>
<?php endforeach; ?>

<button class="bg-yellow-400 hover:bg-yellow-500 text-black px-4 py-2 rounded font-semibold w-full">
Save Character Stats
</button>
</form>

<a href="dashboard.php" class="block text-center mt-4 text-yellow-300 hover:underline">
← Back to User List
</a>

</div>
</body>
</html>
