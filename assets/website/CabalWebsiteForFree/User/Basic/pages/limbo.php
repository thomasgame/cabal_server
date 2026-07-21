<?php
/**
 * CABAL ONLINE - PREMIUM LIMBO (STAKE STYLE)
 * Refined Design Edition
 */

// --- 0. INITIALIZATION ---
$UserNum = 0;
$accountID = 'Unknown';
$isOnline = false;
$ecoin = 0;
$forceGems = 0;
$gameResult = null;
$errorMsg = null;
$siteDatabase = str_replace(']', ']]', DB_SITE);

try {
    $limboConfig = $conn->query("SELECT TOP 1 HouseEdge, IsEnabled FROM [{$siteDatabase}].dbo.LimboSettings")->fetch(PDO::FETCH_ASSOC);
    if (!$limboConfig['IsEnabled']) {
        die("<div style='background:#0f172a; color:#f87171; height:100vh; display:flex; align-items:center; justify-content:center; font-family:sans-serif;'><h2>Limbo is currently under maintenance.</h2></div>");
    }
    $houseEdge = (float)$limboConfig['HouseEdge'];
} catch (Exception $e) {
    $houseEdge = 0.05; // Safety Fallback
}

$lastCurrency = $_POST['currency_type'] ?? 'ecoin';
$lastBet = isset($_POST['bet_amount']) ? (int)$_POST['bet_amount'] : 10;
$lastMultiplier = isset($_POST['target_multiplier']) ? (float)$_POST['target_multiplier'] : 2.00;

// --- 1. AUTH & BALANCES ---
if (isset($username)) {
    $stmtUser = $conn->prepare("SELECT UserNum, ID, Login FROM Account.dbo.cabal_auth_table WHERE ID = :id");
    $stmtUser->execute(['id' => $username]); 
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $UserNum = $userData['UserNum'];
        $accountID = $userData['ID'];
        $isOnline = ($userData['Login'] == 1); 

        $stmtCash = $conn->prepare("SELECT Cash FROM CabalCash.dbo.CashAccount WHERE UserNum = :un");
        $stmtCash->execute(['un' => $UserNum]);
        $ecoin = $stmtCash->fetchColumn() ?: 0;

        $stmtGems = $conn->prepare("SELECT ForcegemHave FROM Server01.dbo.cabal_forcegem_table WHERE UserNum = :un");
        $stmtGems->execute(['un' => $UserNum]);
        $forceGems = $stmtGems->fetchColumn() ?: 0;
    }
}

$isMaintenance = isset($limboConfig['IsEnabled']) && $limboConfig['IsEnabled'] == 0;
$currentBalance = ($lastCurrency === 'forcegem') ? $forceGems : $ecoin;
$hasEnoughBalance = ($currentBalance >= $lastBet);
$canPlay = !$isMaintenance && !$isOnline && $hasEnoughBalance && $UserNum > 0;

// --- 2. GAME LOGIC ---
if (isset($_POST['play_limbo']) && $UserNum > 0) {
    if ($isOnline) {
        $errorMsg = "Please log out of the game first.";
    } elseif (!$hasEnoughBalance) {
        $errorMsg = "Insufficient balance for this bet.";
    } else {
        $bet = (int)$_POST['bet_amount'];
        $target = (float)$_POST['target_multiplier'];
        $currency = $_POST['currency_type'];
        $balance = ($currency === 'forcegem') ? $forceGems : $ecoin;

        if ($target > 1000) $target = 1000;

        if ($bet >= 10 && $target >= 1.01 && $balance >= $bet) {
            try {
                $conn->beginTransaction();

                if ($currency === 'forcegem') {
                    $stmt = $conn->prepare("EXEC Server01.dbo.AddFgems @Fgems = :bet, @UserNum = :un");
                    $stmt->execute(['bet' => ($bet * -1), 'un' => $UserNum]);
                } else {
                    $stmt = $conn->prepare("UPDATE CabalCash.dbo.CashAccount SET Cash = Cash - :bet WHERE UserNum = :un");
                    $stmt->execute(['bet' => $bet, 'un' => $UserNum]);
                }

                $rand = mt_rand(0, 1000000) / 1000000;
                $result = (1 - $houseEdge) / (1 - $rand);
                if ($result < 1) $result = 1.00;
                $result = floor($result * 100) / 100; 

                $winAmount = 0;
                $status = 'LOSS';
                
                if ($result >= $target) {
                    $status = 'WIN';
                    $winAmount = floor($bet * $target);
                    
                    if ($currency === 'forcegem') {
                        $stmtWin = $conn->prepare("EXEC Server01.dbo.AddFgems @Fgems = :win, @UserNum = :un");
                        $stmtWin->execute(['win' => $winAmount, 'un' => $UserNum]);
                    } else {
                        $stmtWin = $conn->prepare("UPDATE CabalCash.dbo.CashAccount SET Cash = Cash + :win WHERE UserNum = :un");
                        $stmtWin->execute(['win' => $winAmount, 'un' => $UserNum]);
                    }
                }

                $stmtLog = $conn->prepare("INSERT INTO [{$siteDatabase}].dbo.LimboLogs (UserNum, AccountID, Currency, BetAmount, TargetMultiplier, ResultMultiplier, WinAmount, Status) VALUES (:un, :aid, :cur, :bet, :tar, :res, :win, :stat)");
                $stmtLog->execute([
                    'un' => $UserNum, 'aid' => $accountID, 'cur' => $currency, 'bet' => $bet,
                    'tar' => $target, 'res' => $result, 'win' => $winAmount, 'stat' => $status
                ]);

                $conn->commit();
                $newBalance = ($currency === 'forcegem') ? ($forceGems - $bet + $winAmount) : ($ecoin - $bet + $winAmount);
                $gameResult = ['result' => $result, 'win' => $winAmount, 'status' => $status, 'newBalance' => $newBalance, 'currency' => $currency];

            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                $errorMsg = "Error: " . $e->getMessage();
            }
        }
    }
}

try {
    $history = $conn->query("SELECT TOP 8 AccountID, ResultMultiplier, WinAmount, Currency FROM [{$siteDatabase}].dbo.LimboLogs ORDER BY LogDate DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $history = []; }

function mask($id) { return substr($id, 0, 2) . '***' . substr($id, -1); }
?>

<style>
    :root {
        --bg-main: #0f172a;
        --bg-panel: #1e293b;
        --bg-input: #020617;
        --accent: #38bdf8;
        --accent-hover: #0ea5e9;
        --success: #10b981;
        --danger: #ef4444;
        --text-muted: #94a3b8;
    }

    .limbo-wrapper {
        background: var(--bg-main);
        color: #ffffff;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        padding: 40px 20px;
        min-height: 800px;
    }

    .limbo-container {
        max-width: 1100px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 24px;
    }

    /* Header Stats */
    .limbo-header {
        max-width: 1100px;
        margin: 0 auto 24px auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--bg-panel);
        padding: 15px 25px;
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,0.05);
    }

    .balance-card {
        display: flex;
        gap: 20px;
    }

    .bal-item {
        display: flex;
        flex-direction: column;
    }

    .bal-label {
        font-size: 10px;
        font-weight: 800;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .bal-value {
        font-size: 16px;
        font-weight: 700;
        color: #fff;
    }

    /* Controls */
    .control-sidebar {
        background: var(--bg-panel);
        padding: 24px;
        border-radius: 20px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3);
    }

    .input-wrapper {
        margin-bottom: 20px;
    }

    .input-wrapper label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: var(--text-muted);
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .custom-input {
        width: 100%;
        background: var(--bg-input);
        border: 2px solid #334155;
        border-radius: 12px;
        padding: 14px;
        color: #fff;
        font-size: 15px;
        font-weight: 600;
        transition: all 0.2s;
        box-sizing: border-box;
    }

    .custom-input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
    }

    .btn-bet {
        width: 100%;
        padding: 18px;
        border-radius: 12px;
        border: none;
        background: var(--accent);
        color: var(--bg-input);
        font-size: 16px;
        font-weight: 800;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 0 0 #0369a1;
    }

    .btn-bet:hover {
        transform: translateY(-1px);
        background: #7dd3fc;
    }

    .btn-bet:active {
        transform: translateY(2px);
        box-shadow: none;
    }

    .btn-bet:disabled {
        background: #334155 !important;
        color: #64748b !important;
        box-shadow: none;
        cursor: not-allowed;
    }

    /* Game Display */
    .game-display {
        background: var(--bg-input);
        border-radius: 20px;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.05);
    }

    .multiplier-val {
        font-size: 100px;
        font-weight: 900;
        color: #fff;
        text-shadow: 0 10px 30px rgba(0,0,0,0.5);
        z-index: 2;
    }

    .multiplier-val.win { color: var(--success); text-shadow: 0 0 40px rgba(16, 185, 129, 0.3); }
    .multiplier-val.loss { color: var(--danger); }

    .target-info {
        font-weight: 700;
        color: var(--text-muted);
        background: rgba(255,255,255,0.05);
        padding: 8px 20px;
        border-radius: 50px;
        margin-top: 10px;
    }

    /* History Feed */
    .history-section {
        max-width: 1100px;
        margin: 24px auto 0 auto;
        background: var(--bg-panel);
        padding: 24px;
        border-radius: 20px;
    }

    .history-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
        margin-top: 15px;
    }

    .history-card {
        background: var(--bg-input);
        padding: 12px 16px;
        border-radius: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid rgba(255,255,255,0.03);
    }

    .hist-user { font-size: 11px; color: var(--text-muted); font-weight: 600; }
    .hist-mult { font-weight: 800; font-size: 14px; }

    .chance-label {
        font-size: 11px;
        color: var(--accent);
        margin-top: 5px;
        display: block;
        text-align: right;
    }
</style>

<div class="limbo-wrapper">
    <div class="limbo-header">
        <div class="balance-card">
            <div class="bal-item">
                <span class="bal-label">eCoin Balance</span>
                <span class="bal-value" id="bal-ecoin">🪙 <?= number_format($ecoin) ?></span>
            </div>
            <div class="bal-item">
                <span class="bal-label">Force Gems</span>
                <span class="bal-value" id="bal-forcegem">💎 <?= number_format($forceGems) ?></span>
            </div>
        </div>
        <div style="text-align: right">
            <div style="font-weight: 900; color: var(--accent); font-size: 18px; letter-spacing: 1px;">LIMBO</div>
            <span style="font-size: 10px; opacity: 0.6;">House Edge: <?= $houseEdge * 100 ?>%</span>
        </div>
    </div>

    <div class="limbo-container">
        <form method="POST" class="control-sidebar" id="limboForm">
            <div class="input-wrapper">
                <label>Currency</label>
                <select name="currency_type" class="custom-input" onchange="this.form.submit()">
                    <option value="ecoin" <?= $lastCurrency=='ecoin'?'selected':'' ?>>eCoin (Cabal Cash)</option>
                    <option value="forcegem" <?= $lastCurrency=='forcegem'?'selected':'' ?>>Force Gems</option>
                </select>
            </div>

            <div class="input-wrapper">
                <label>Bet Amount</label>
                <input type="number" name="bet_amount" id="bet-input" class="custom-input" value="<?= $lastBet ?>" min="10" step="10" oninput="validateBet()">
            </div>

            <div class="input-wrapper">
                <label>Target Multiplier</label>
                <input type="number" name="target_multiplier" id="target-input" class="custom-input" value="<?= number_format($lastMultiplier, 2, '.', '') ?>" min="1.01" max="1000" step="0.01" oninput="updateChance()">
                <span class="chance-label" id="chance-text">Win Chance: <?= number_format((100 - ($houseEdge * 100)) / $lastMultiplier, 2) ?>%</span>
            </div>

            <button type="submit" name="play_limbo" id="bet-button" class="btn-bet" <?= (!$canPlay) ? 'disabled' : '' ?>>
                <?php 
                    if ($isMaintenance) echo 'MAINTENANCE';
                    elseif ($isOnline) echo 'LOGOUT TO PLAY';
                    elseif (!$hasEnoughBalance) echo 'NO BALANCE';
                    elseif ($UserNum <= 0) echo 'LOGIN FIRST';
                    else echo 'BET';
                ?>
            </button>

            <?php if($errorMsg): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 12px; border-radius: 8px; font-size: 12px; margin-top: 15px; border: 1px solid var(--danger); text-align: center;">
                    <?= $errorMsg ?>
                </div>
            <?php endif; ?>
        </form>

        <div class="game-display">
            <div style="position: absolute; width: 300px; height: 300px; background: var(--accent); filter: blur(150px); opacity: 0.05; pointer-events: none;"></div>
            
            <div id="result-display" class="multiplier-val">1.00x</div>
            <div id="status-sub" class="target-info">
                TARGET: <?= number_format($lastMultiplier, 2) ?>x
            </div>
        </div>
    </div>

    <div class="history-section">
        <label style="font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Recent Global Rolls</label>
        <div class="history-grid">
            <?php foreach($history as $h): ?>
                <div class="history-card">
                    <span class="hist-user"><?= mask($h['AccountID']) ?></span>
                    <span class="hist-mult" style="color:<?= $h['WinAmount']>0? 'var(--success)':'var(--danger)' ?>">
                        <?= number_format($h['ResultMultiplier'], 2) ?>x
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    function updateChance() {
        const target = parseFloat(document.getElementById('target-input').value) || 1.01;
        const edge = <?= $houseEdge ?>;
        const chance = ((1 - edge) / target) * 100;
        document.getElementById('chance-text').innerText = `Win Chance: ${chance.toFixed(2)}%`;
    }

    function validateBet() {
        const bet = parseInt(document.getElementById('bet-input').value) || 0;
        const balance = <?= (int)$currentBalance ?>;
        const isOnline = <?= $isOnline ? 'true' : 'false' ?>;
        const isMaintenance = <?= $isMaintenance ? 'true' : 'false' ?>;
        const btn = document.getElementById('bet-button');

        if (isMaintenance || isOnline) return;

        if (bet > balance) {
            btn.disabled = true;
            btn.innerText = 'NO BALANCE';
        } else if (bet < 10) {
            btn.disabled = true;
            btn.innerText = 'MIN 10';
        } else {
            btn.disabled = false;
            btn.innerText = 'BET';
        }
    }

    <?php if($gameResult): ?>
    const res = <?= json_encode($gameResult) ?>;
    const display = document.getElementById('result-display');
    const sub = document.getElementById('status-sub');
    
    let start = performance.now();
    const duration = 700; 

    function animate(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing out function
        const easeOut = 1 - Math.pow(1 - progress, 3);
        const currentVal = 1 + (res.result - 1) * easeOut;
        
        display.innerText = currentVal.toFixed(2) + 'x';

        if (progress < 1) {
            requestAnimationFrame(animate);
        } else {
            display.innerText = res.result.toFixed(2) + 'x';
            display.classList.add(res.status === 'WIN' ? 'win' : 'loss');
            
            if(res.win > 0) {
                sub.innerHTML = `<span style="color:var(--success)">WIN: +${res.win.toLocaleString()}</span>`;
            } else {
                sub.innerHTML = `<span style="color:var(--danger)">BUSTED</span>`;
            }
            
            // Update Top Balance
            const balEl = document.getElementById('bal-' + res.currency);
            if(balEl) balEl.innerText = (res.currency === 'forcegem' ? '💎 ' : '🪙 ') + res.newBalance.toLocaleString();
        }
    }
    requestAnimationFrame(animate);
    <?php endif; ?>
</script>