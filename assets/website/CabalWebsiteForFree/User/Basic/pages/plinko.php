<?php
/**
 * CABAL ONLINE - FORCE GEM PLINKO (UPDATED DESIGN)
 */ 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- DATABASE CONNECTION ---
// Ensure $conn is defined before this point via PDO

$UserNum = 0;
$accountID = 'Unknown';
$isOnline = false;
$forceGems = 0;
$gameResult = null;
$errorMsg = null;
$cooldownSeconds = 2; 

$lastBet = isset($_POST['bet_amount']) ? (int)$_POST['bet_amount'] : 10;
$lastRisk = $_POST['risk_type'] ?? 'x8';

$allMultipliers = [
    'x8'   => [45, 12, 2, 1, 0, 1, 2, 12, 45], 
    'x12'  => [170, 24, 8, 2, 0.8, 0, 0, 0.8, 2, 8, 24, 170],
    'x100' => [1000, 130, 26, 9, 4, 2, 0.8, 0, 0.8, 2, 4, 9, 26, 130, 1000]
];

/**
 * HELPER: Fetch settings with Hardcoded Fallback
 */
function getPlinkoSettings($conn, $risk) {
    $defaults = ['IsEnabled' => 1, 'HouseBias' => 0.15];
    try {
        $siteDatabase = str_replace(']', ']]', DB_SITE);
        $stmt = $conn->prepare("SELECT IsEnabled, HouseBias FROM [{$siteDatabase}].dbo.PlinkoSettings WHERE UPPER(TRIM(RiskType)) = UPPER(TRIM(:r))");
        $stmt->execute(['r' => $risk]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : $defaults; 
    } catch(Exception $e) { 
        return $defaults; 
    }
}

// 1. AJAX STATUS CHECKER
if (isset($_GET['check_status'])) {
    header('Content-Type: application/json');
    $settings = getPlinkoSettings($conn, $_GET['check_status']);
    echo json_encode(['enabled' => (int)$settings['IsEnabled'] === 1]);
    exit;
}

// 2. FETCH USER
if (isset($username)) { 
    $stmtUser = $conn->prepare("SELECT UserNum, ID, Login FROM Account.dbo.cabal_auth_table WHERE ID = :id");
    $stmtUser->execute(['id' => $username]); 
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $UserNum = $userData['UserNum'];
        $accountID = $userData['ID'];
        $isOnline = ($userData['Login'] == 1); 

        $stmtGems = $conn->prepare("SELECT ForcegemHave FROM Server01.dbo.cabal_forcegem_table WHERE UserNum = :un");
        $stmtGems->execute(['un' => $UserNum]);
        $forceGems = $stmtGems->fetchColumn() ?: 0;
    }
}

$settings = getPlinkoSettings($conn, $lastRisk);
$plinkoEnabled = ((int)$settings['IsEnabled'] === 1);
$now = time();
$lastPlayTime = $_SESSION['plinko_last_play'] ?? 0;
$onCooldown = ($now - $lastPlayTime < $cooldownSeconds);
$hasEnoughBalance = ($forceGems >= $lastBet);
$canPlay = $plinkoEnabled && !$isOnline && $hasEnoughBalance && $UserNum > 0 && !$onCooldown;

// 3. GAME LOGIC
if (isset($_POST['action_drop']) && $UserNum > 0 && $canPlay) {
    $bet = (int)$_POST['bet_amount'];
    $risk = $_POST['risk_type'];
    
    if ($bet >= 10 && $bet <= 500 && isset($allMultipliers[$risk])) {
        try {
            $_SESSION['plinko_last_play'] = time();
            $bias = (float)$settings['HouseBias'];

            $conn->beginTransaction();
            $stmt = $conn->prepare("EXEC Server01.dbo.AddFgems @Fgems = :bet, @UserNum = :un");
            $stmt->execute(['bet' => ($bet * -1), 'un' => $UserNum]);

            $multipliers = $allMultipliers[$risk];
            $rowCount = count($multipliers) - 1;
            $path = []; $rightMoves = 0;

            for ($i = 0; $i < $rowCount; $i++) {
                $center = $rowCount / 2;
                $probRight = 0.5;
                if ($rightMoves < $center) $probRight += $bias; 
                elseif ($rightMoves > $center) $probRight -= $bias;
                
                $move = (mt_rand(1, 1000) / 1000 <= $probRight) ? 1 : 0;
                $path[] = $move;
                $rightMoves += $move;
            }

            $selectedMult = $multipliers[$rightMoves];
            $winAmount = floor($bet * $selectedMult);

            if ($winAmount > 0) {
                $stmtWin = $conn->prepare("EXEC Server01.dbo.AddFgems @Fgems = :win, @UserNum = :un");
                $stmtWin->execute(['win' => $winAmount, 'un' => $UserNum]);
            }

            $siteDatabase = str_replace(']', ']]', DB_SITE);
            $stmtLog = $conn->prepare("INSERT INTO [{$siteDatabase}].dbo.PlinkoLogs (UserNum, AccountID, BetAmount, TargetRisk, ResultMultiplier, WinAmount) VALUES (:un, :aid, :bet, :risk, :res, :win)");
            $stmtLog->execute(['un' => $UserNum, 'aid' => $accountID, 'bet' => $bet, 'risk' => $risk, 'res' => $selectedMult, 'win' => $winAmount]);
            
            $conn->commit();
            $gameResult = ['path' => $path, 'multiplier' => $selectedMult, 'win' => $winAmount, 'newBalance' => ($forceGems - $bet + $winAmount)];
            $forceGems = $gameResult['newBalance'];

        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            error_log("Plinko transaction failed: " . $e->getMessage());
            $errorMsg = "System Error: Please try again.";
        }
    }
}

$history = [];
try {
    $siteDatabase = str_replace(']', ']]', DB_SITE);
    $stmtH = $conn->prepare("SELECT TOP 15 AccountID, TargetRisk, ResultMultiplier, WinAmount, LogDate FROM [{$siteDatabase}].dbo.PlinkoLogs ORDER BY LogDate DESC");
    $stmtH->execute();
    $history = $stmtH->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root { 
        --bg: #0b0f1a; 
        --panel: #161c2d; 
        --accent: #00d4ff; 
        --gold: #fbbf24;
        --green: #00ff88; 
        --border: #2d3748;
    }
    .plinko-body { background: var(--bg); color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
    
    /* Layout Container */
    .game-wrapper { 
        max-width: 1250px; 
        margin: 0 auto; 
        display: grid; 
        grid-template-columns: 260px 1fr 300px; 
        gap: 15px; 
    }

    /* Header */
    .header { 
        grid-column: 1 / -1; 
        background: linear-gradient(90deg, var(--panel), #1e293b); 
        padding: 15px 25px; 
        border-radius: 10px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        border: 1px solid var(--border);
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }

    /* Side Panels (Controls & Logs) */
    .side-panel { 
        background: var(--panel); 
        padding: 20px; 
        border-radius: 10px; 
        border: 1px solid var(--border);
        height: 600px;
        display: flex;
        flex-direction: column;
    }

    /* Board Area */
    .board-container { 
        background: radial-gradient(circle, #1a202c 0%, #000 100%);
        border-radius: 10px; 
        position: relative; 
        height: 600px; 
        overflow: hidden; 
        border: 1px solid var(--border);
        box-shadow: inset 0 0 50px rgba(0,0,0,0.5);
    }

    /* Controls UI */
    .control-label { font-size: 11px; text-transform: uppercase; color: #94a3b8; letter-spacing: 1px; margin-bottom: 5px; display: block; }
    input, select { width: 100%; padding: 12px; margin-bottom: 20px; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 6px; outline: none; }
    input:focus, select:focus { border-color: var(--accent); }
    
    .btn-drop { 
        width: 100%; padding: 18px; 
        background: linear-gradient(135deg, #059669 0%, #10b981 100%); 
        border: none; color: #fff; font-weight: 800; border-radius: 6px; cursor: pointer; 
        text-transform: uppercase; transition: 0.3s;
    }
    .btn-drop:hover:not(:disabled) { transform: translateY(-2px); filter: brightness(1.1); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4); }
    .btn-drop:disabled { background: #334155; opacity: 0.5; cursor: not-allowed; }

    /* Plinko Components */
    .peg { width: 6px; height: 6px; background: #475569; position: absolute; border-radius: 50%; box-shadow: 0 0 2px rgba(0,0,0,0.5); }
    .peg.active { background: #fff; box-shadow: 0 0 12px #fff; transform: scale(1.8); }
    .ball { width: 14px; height: 14px; background: var(--gold); position: absolute; border-radius: 50%; z-index: 10; box-shadow: 0 0 15px var(--gold); }
    
    .mult-row { position: absolute; bottom: 15px; width: 94%; left: 3%; display: flex; gap: 4px; }
    .m-box { flex: 1; height: 30px; font-size: 10px; display: flex; align-items: center; justify-content: center; background: #1e293b; border: 1px solid #334155; border-radius: 4px; font-weight: bold; color: #94a3b8; }
    .active-win { background: var(--gold) !important; color: #000 !important; transform: scale(1.1) translateY(-5px); transition: 0.2s; border-color: #fff; }

    /* History Table */
    .history-title { font-size: 14px; font-weight: bold; margin-bottom: 15px; color: var(--accent); border-bottom: 1px solid var(--border); padding-bottom: 10px; }
    .log-scroll { overflow-y: auto; flex-grow: 1; scrollbar-width: thin; scrollbar-color: var(--border) transparent; }
    table { width: 100%; border-collapse: collapse; }
    th { position: sticky; top: 0; background: var(--panel); z-index: 5; text-align: left; font-size: 10px; color: #64748b; text-transform: uppercase; padding-bottom: 10px; }
    td { padding: 8px 0; font-size: 11px; border-bottom: 1px solid #1e293b; }
    .val-win { color: var(--green); font-weight: bold; }
</style>

<div class="plinko-body">
    <div class="game-wrapper">
        <div class="header">
            <div>
                <h2 style="color:var(--gold); margin:0; font-style: italic; letter-spacing: 2px;">FORCE PLINKO</h2>
                <span style="font-size: 10px; color: #64748b;">NEVRETH ARCHIVE PROTOCOL v2.1</span>
            </div>
            <div id="gem-balance" style="font-size: 1.4rem; font-weight: 800; color: var(--gold); text-shadow: 0 0 10px rgba(251, 191, 36, 0.3);">
                <i class="fas fa-gem"></i> <?= number_format($forceGems) ?>
            </div>
        </div>

        <div class="side-panel">
            <form method="POST">
                <input type="hidden" name="action_drop" value="1">
                
                <label class="control-label">Input Bet</label>
                <input type="number" name="bet_amount" value="<?= $lastBet ?>" min="10" max="500">
                
                <label class="control-label">Risk Profile</label>
                <select name="risk_type" id="risk-select" onchange="drawBoard()">
                    <?php foreach($allMultipliers as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $lastRisk==$k?'selected':'' ?>><?= strtoupper($k) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="btn-drop" <?= !$canPlay ? 'disabled' : '' ?>>
                    <?php 
                        if (!$plinkoEnabled) echo '<i class="fas fa-power-off"></i> Offline';
                        elseif ($isOnline) echo '<i class="fas fa-user-slash"></i> Logout First';
                        elseif (!$hasEnoughBalance) echo '<i class="fas fa-times"></i> No Gems';
                        else echo '<i class="fas fa-play"></i> Drop Ball';
                    ?>
                </button>
                <?php if($errorMsg) echo "<p style='color:#ff4d4d; font-size:11px; margin-top:15px; text-align:center;'>$errorMsg</p>"; ?>
            </form>
            
            <div style="margin-top: auto; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 6px; font-size: 10px; color: #64748b;">
                <p><i class="fas fa-info-circle"></i> Minimum bet: 10 Gems</p>
                <p><i class="fas fa-info-circle"></i> Cooldown: 2 Seconds</p>
            </div>
        </div>

        <div class="board-container" id="board">
            <div class="mult-row" id="mult-row"></div>
        </div>

        <div class="side-panel">
            <div class="history-title"><i class="fas fa-list-ul"></i> GLOBAL HISTORY</div>
            <div class="log-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Risk</th>
                            <th>Mult</th>
                            <th style="text-align:right">Win</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($history as $h): ?>
                        <tr>
                            <td style="color:#94a3b8"><code><?= substr($h['AccountID'],0,4) ?>..</code></td>
                            <td><span style="font-size: 9px; padding: 2px 4px; background: #0f172a; border-radius: 3px;"><?= $h['TargetRisk'] ?></span></td>
                            <td><?= $h['ResultMultiplier'] ?>x</td>
                            <td class="val-win" style="text-align:right"><?= number_format($h['WinAmount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const board = document.getElementById('board');
    const multRow = document.getElementById('mult-row');
    const riskSelect = document.getElementById('risk-select');
    const multipliers = <?= json_encode($allMultipliers) ?>;
    let pegMap = [];

    function drawBoard() {
        board.querySelectorAll('.peg').forEach(p => p.remove());
        const risk = riskSelect.value;
        const currentMults = multipliers[risk];
        const rows = currentMults.length - 1;
        const colW = (board.offsetWidth - 40) / (rows + 2);
        const rowH = (board.offsetHeight - 120) / rows;
        pegMap = [];

        for (let r = 0; r <= rows; r++) {
            const pins = r + 3;
            const rowW = (pins - 1) * colW;
            pegMap[r] = [];
            for (let i = 0; i < pins; i++) {
                const peg = document.createElement('div');
                peg.className = 'peg';
                const x = (board.offsetWidth / 2) - (rowW / 2) + (i * colW);
                const y = 50 + (r * rowH);
                peg.style.left = x + 'px';
                peg.style.top = y + 'px';
                board.appendChild(peg);
                pegMap[r].push({ x, y, el: peg });
            }
        }

        multRow.innerHTML = '';
        currentMults.forEach((m, idx) => {
            const d = document.createElement('div');
            d.className = 'm-box';
            d.id = 'm-' + idx;
            d.innerText = m + 'x';
            multRow.appendChild(d);
        });
    }

    drawBoard();

    <?php if($gameResult): ?>
    (function() {
        const res = <?= json_encode($gameResult) ?>;
        const ball = document.createElement('div');
        ball.className = 'ball';
        board.appendChild(ball);

        let curCol = 1; 
        const speed = 180;

        function animate(step) {
            if (step < res.path.length) {
                const peg = pegMap[step][curCol];
                ball.style.transition = `all ${speed}ms ease-in-out`;
                ball.style.left = peg.x + 'px';
                ball.style.top = peg.y + 'px';
                
                setTimeout(() => {
                    peg.el.classList.add('active');
                    setTimeout(() => peg.el.classList.remove('active'), 100);
                }, speed * 0.5);

                curCol += res.path[step];
                setTimeout(() => animate(step + 1), speed);
            } else {
                const finalIdx = res.path.reduce((a, b) => a + b, 0);
                const target = document.getElementById('m-' + finalIdx);
                ball.style.top = (board.offsetHeight - 45) + 'px';
                setTimeout(() => {
                    target.classList.add('active-win');
                    ball.remove();
                    document.getElementById('gem-balance').innerHTML = '<i class="fas fa-gem"></i> ' + Number(res.newBalance).toLocaleString();
                }, speed);
            }
        }
        ball.style.left = (board.offsetWidth / 2) + 'px';
        ball.style.top = '10px';
        setTimeout(() => animate(0), 100);
    })();
    <?php endif; ?>
</script>