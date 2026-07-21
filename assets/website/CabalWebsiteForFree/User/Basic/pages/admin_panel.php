<?php
// Secure inclusion check: this file should only be included via dashboard.php
if (!isset($isAdmin) || !$isAdmin) {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    exit;
}

// Start session to handle flash messages after refresh
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * COMPATIBILITY LAYER: Direct SQL Execution
 */
function executeAdminQuery($db, $query, $params = []) {
    $stmt = $db->getConnection()->prepare($query);
    return $stmt->execute($params);
}

// Cabal Constant Definitions
$bindings = [
    "None" => 0,
    "Account Bind" => 4096,
    "Character Bind" => 524288,
    "Character Bind When Use" => 1572864,
    "Character Bind Extended" => 528384
];

$durations = [
    "PERMANENT" => 0, "1 Hour" => 1, "2 Hours" => 2, "3 Hours" => 3, "4 Hours" => 4, "5 Hours" => 5,
    "6 Hours" => 6, "10 Hours" => 7, "12 Hours" => 8, "1 Day" => 9, "3 Days" => 10, "5 Days" => 11,
    "7 Days" => 12, "10 Days" => 13, "14 Days" => 14, "15 Days" => 15, "20 Days" => 16, "30 Days" => 17,
    "45 Days" => 18, "60 Days" => 19, "90 Days" => 20, "100 Days" => 21, "120 Days" => 22, "180 Days" => 23,
    "270 Days" => 24, "365 Days" => 25
];

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['admin_action'] ?? '';
    $status = "failed";
    $msg = "Unknown Error";

    try {
        // SEND E-COINS
        if ($action === 'send_cash') {
            $targetUser = trim($_POST['target_account']);
            $amount = intval($_POST['amount']);
            $stmt = $db->getConnection()->prepare("SELECT UserNum FROM Account.dbo.cabal_auth_table WHERE ID = ?");
            $stmt->execute([$targetUser]);
            $userNum = $stmt->fetchColumn();
            if ($userNum) {
                executeAdminQuery($db, "UPDATE CabalCash.dbo.CashAccount SET Cash = Cash + ? WHERE UserNum = ?", [$amount, $userNum]);
                $status = "success"; $msg = "Sent $amount E-Coins to $targetUser.";
            } else { throw new Exception("Account ID not found."); }
        }

        // SEND SINGLE ITEM
        if ($action === 'send_char_item') {
            $charName = trim($_POST['char_name']);
            $itemID = (int)$_POST['itemID'];
            $itemOpt = (int)$_POST['item_option'];
            $bindValue = (float)$_POST['binding'];
            $durationID = (int)$_POST['duration'];

            $stmt = $db->getConnection()->prepare("SELECT (CharacterIdx / 16) as CalculatedUserNum FROM Server01.dbo.cabal_character_table WHERE Name = ?");
            $stmt->execute([$charName]);
            $uNum = $stmt->fetchColumn();

            if ($uNum) {
                // Simplified calculation without upgrade offsets
                $itemKindIdx = $itemID + $bindValue;
                executeAdminQuery($db, "INSERT INTO CabalCash.dbo.MyCashItem (TranNo, ServerIdx, UserNum, ItemKindIdx, ItemOpt, DurationIdx) VALUES (-708, 1, ?, ?, ?, ?)", [
                    (int)$uNum, (int)$itemKindIdx, (int)$itemOpt, (int)$durationID
                ]);
                $status = "success"; $msg = "Item sent to $charName successfully.";
            } else { throw new Exception("Character not found."); }
        }
    } catch (Exception $e) {
        $status = "failed";
        $msg = $e->getMessage();
    }

    $_SESSION['toast_status'] = $status;
    $_SESSION['toast_msg'] = $msg;
    echo "<script>window.location.href = window.location.href;</script>";
    exit;
}

$toastStatus = $_SESSION['toast_status'] ?? null;
$toastMsg = $_SESSION['toast_msg'] ?? null;
unset($_SESSION['toast_status'], $_SESSION['toast_msg']);
?>

<style>
:root {
    --card-bg: #1a1b2e;
    --input-bg: #252745;
    --border-color: #343761;
    --accent-purple: #8b5cf6;
    --accent-gold: #fbbf24;
    --accent-green: #10b981;
    --text-main: #e2e8f0;
}

.admin-container { max-width: 1000px; margin: 0 auto; padding: 20px; color: var(--text-main); font-family: 'Segoe UI', sans-serif; }
/* Adjusted to 2 columns for the remaining cards */
.dashboard-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px; }
.dashboard-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; }
.form-input { width: 100%; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 6px; padding: 10px; color: #fff; margin-bottom: 12px; box-sizing: border-box; }
.form-btn { width: 100%; padding: 12px; border-radius: 6px; border: none; font-weight: bold; cursor: pointer; color: #fff; transition: opacity 0.2s; margin-top: 10px; }
.form-btn:hover { opacity: 0.8; }

#toast-container { position: fixed; top: 20px; right: 20px; z-index: 10001; min-width: 300px; display: none; animation: slideIn 0.4s ease forwards; }
.toast-box { padding: 15px 20px; border-radius: 8px; color: white; font-weight: 500; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4); }
.toast-box.success { background: #059669; border-left: 6px solid #047857; }
.toast-box.failed { background: #dc2626; border-left: 6px solid #b91c1c; }

@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
</style>

<div class="admin-container">
    <div id="toast-container" style="<?= ($toastStatus) ? 'display:block;' : '' ?>">
        <div class="toast-box <?= $toastStatus ?>">
            <span id="toast-text"><?= htmlspecialchars($toastMsg) ?></span>
            <span style="margin-left:15px; cursor:pointer;" onclick="closeToast()">×</span>
        </div>
    </div>

    <h2>Admin Command Center</h2>

    <div class="dashboard-grid">
        <div class="dashboard-card" style="border-top: 4px solid var(--accent-gold);">
            <h3>Send E-Coins</h3>
            <form method="POST">
                <input type="hidden" name="admin_action" value="send_cash">
                <label style="font-size: 0.8rem;">Account ID:</label>
                <input type="text" name="target_account" class="form-input" placeholder="Enter ID" required>
                <label style="font-size: 0.8rem;">Amount:</label>
                <input type="number" name="amount" class="form-input" placeholder="Enter amount" required>
                <button type="submit" class="form-btn" style="background: var(--accent-gold); color: #000;">Add Cash</button>
            </form>
        </div>

        <div class="dashboard-card" style="border-top: 4px solid var(--accent-green);">
            <h3>Send Item to Character</h3>
            <form method="POST">
                <input type="hidden" name="admin_action" value="send_char_item">
                
                <label style="font-size: 0.8rem;">Char Name:</label>
                <input type="text" name="char_name" class="form-input" placeholder="Character Name" required>
                
                <label style="font-size: 0.8rem;">Item ID:</label>
                <input type="number" name="itemID" class="form-input" placeholder="Item ID" required>
                
                <label style="font-size: 0.8rem;">Item Option:</label>
                <input type="number" name="item_option" class="form-input" value="0">

                <label style="font-size: 0.8rem;">Item Binding:</label>
                <select name="binding" class="form-input">
                    <?php foreach ($bindings as $k => $v): ?>
                        <option value="<?= $v ?>"><?= $k ?></option>
                    <?php endforeach; ?>
                </select>
                
                <label style="font-size: 0.8rem;">Duration:</label>
                <select name="duration" class="form-input">
                    <?php foreach ($durations as $k => $v): ?>
                        <option value="<?= $v ?>"><?= $k ?></option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="form-btn" style="background: var(--accent-green);">Send Item</button>
            </form>
        </div>
    </div>
</div>

<script>
function closeToast() {
    const container = document.getElementById('toast-container');
    if(!container) return;
    container.style.animation = "slideOut 0.4s ease forwards";
    setTimeout(() => { container.style.display = 'none'; }, 400);
}

window.onload = function() {
    const toast = document.getElementById('toast-container');
    if (toast && toast.style.display !== 'none') {
        setTimeout(closeToast, 4000);
    }
};
</script>