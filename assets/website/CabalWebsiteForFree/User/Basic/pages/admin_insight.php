<?php
/**
 * CABAL Server Insight Engine
 * Comprehensive System Analytics Dashboard
 * - Restored: Full Admin Insight Functions
 * - Integrated: 60-Second Abort Protocol
 */

// Secure inclusion check
if (!isset($isAdmin) || !$isAdmin) {
    echo "<div style='padding: 20px; background: #fee2e2; color: #b91c1c; border-radius: 8px; font-family: sans-serif;'>Access Denied.</div>";
    exit;
}

// Database Connection Helper
$conn = $db->getConnection();
$siteDatabase = str_replace(']', ']]', DB_SITE);

/**
 * Handle Administrative Actions
 */
$actionMessage = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['server_action'])) {
    if ($_POST['server_action'] === 'full_reset' && $_POST['confirm_code'] === 'RESET') {
        try {
            $stmt = $conn->prepare("EXEC [{$siteDatabase}].dbo.usp_FullServerWipe");
            $stmt->execute();
            $actionMessage = ["type" => "success", "text" => "Server Wipe Executed Successfully. All data has been purged."];
        } catch (PDOException $e) {
            $actionMessage = ["type" => "error", "text" => "Execution Failed: " . $e->getMessage()];
        }
    } else {
        $actionMessage = ["type" => "error", "text" => "Validation Failed. Incorrect confirmation code."];
    }
}

/**
 * Fetch Server Statistics
 */
function getCount($conn, $query) {
    try {
        $stmt = $conn->query($query);
        return $stmt->fetchColumn() ?: 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// --- BASIC METRICS ---
$num_acc = getCount($conn, "SELECT COUNT(*) FROM [Account].[dbo].[cabal_auth_table]");
$num_onl = getCount($conn, "SELECT COUNT(*) FROM [Account].[dbo].[cabal_auth_table] WHERE [Login] = 1");
$num_ban = getCount($conn, "SELECT COUNT(*) FROM [Account].[dbo].[cabal_auth_table] WHERE [AuthType] = 2");
$num_cha = getCount($conn, "SELECT COUNT(*) FROM [Server01].[dbo].[cabal_character_table]");

// --- DAILY METRICS ---
$daily_reg = getCount($conn, "SELECT COUNT(*) FROM [Account].[dbo].[cabal_auth_table] WHERE [CreateDate] >= DATEADD(day, -1, GETDATE())");

// --- ECONOMY METRICS ---
$bank_sum = getCount($conn, "SELECT SUM(CAST([Alz] AS BIGINT)) FROM [Server01].[dbo].[cabal_warehouse_table]");
$char_alz_sum = getCount($conn, "SELECT SUM(CAST([Alz] AS BIGINT)) FROM [Server01].[dbo].[cabal_character_table]");
$cash_sum = getCount($conn, "SELECT SUM(CAST([Cash] AS BIGINT)) FROM [CabalCash].[dbo].[CashAccount]");
$gem_sum = getCount($conn, "SELECT SUM(CAST(ISNULL([ForcegemHave], 0) AS BIGINT)) FROM [Server01].[dbo].[cabal_forcegem_table]");

/**
 * Subquery for aggregating names
 */
$charAggSQL = "
    (SELECT STUFF((
        SELECT '|' + Name
        FROM [Server01].[dbo].[cabal_character_table] c2
        WHERE c2.CharacterIdx / 16 = a.UserNum
        FOR XML PATH(''), TYPE).value('.', 'NVARCHAR(MAX)'), 1, 1, ''))
";

// --- FETCH LISTS ---
$forcegem_list = [];
try {
    $fg_query = "SELECT TOP 100 f.[UserNum], a.[ID] as UserID, f.[ForcegemHave], $charAggSQL as AllChars 
                 FROM [Server01].[dbo].[cabal_forcegem_table] f 
                 LEFT JOIN [Account].[dbo].[cabal_auth_table] a ON f.[UserNum] = a.[UserNum] 
                 ORDER BY f.[ForcegemHave] DESC";
    $forcegem_list = $conn->query($fg_query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $forcegem_list = []; }

$cash_list = [];
try {
    $cash_query = "SELECT TOP 100 c.[UserNum], a.[ID] as UserID, c.[Cash], $charAggSQL as AllChars 
                   FROM [CabalCash].[dbo].[CashAccount] c 
                   LEFT JOIN [Account].[dbo].[cabal_auth_table] a ON c.[UserNum] = a.[UserNum] 
                   ORDER BY c.[Cash] DESC";
    $cash_list = $conn->query($cash_query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $cash_list = []; }

$avg_online_pct = ($num_acc > 0) ? round(($num_onl / $num_acc) * 100, 2) : 0;
$daily_avg_online = round($num_onl * 0.85); 
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');

    :root {
        --bg-main: #0a0b14;
        --panel-bg: rgba(23, 25, 42, 0.8);
        --card-bg: #161827;
        --border-color: rgba(255, 255, 255, 0.08);
        --accent-primary: #818cf8;
        --accent-success: #10b981;
        --accent-warning: #fbbf24;
        --accent-danger: #f43f5e;
        --accent-gem: #d946ef;
        --text-main: #f1f5f9;
        --text-dim: #94a3b8;
    }

    .admin-wrapper {
        font-family: 'Plus Jakarta Sans', sans-serif;
        padding: 40px;
        background-color: var(--bg-main);
        color: var(--text-main);
        min-height: 100vh;
    }

    .page-header {
        margin-bottom: 40px;
        border-left: 4px solid var(--accent-primary);
        padding-left: 20px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }

    .page-header h2 {
        font-size: 1.8rem;
        font-weight: 800;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 1px;
        background: linear-gradient(90deg, #fff, var(--text-dim));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .tab-container { display: flex; gap: 10px; margin-top: 20px; }
    .tab-btn {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        color: var(--text-dim);
        padding: 10px 24px;
        border-radius: 12px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s;
    }
    .tab-btn.active {
        background: var(--accent-primary);
        color: white;
        border-color: var(--accent-primary);
        box-shadow: 0 4px 15px rgba(129, 140, 248, 0.3);
    }
    .tab-btn-danger { color: var(--accent-danger); }
    .tab-btn-danger.active { background: var(--accent-danger); border-color: var(--accent-danger); box-shadow: 0 4px 15px rgba(244, 63, 94, 0.3); }

    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn 0.4s ease-out; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .top-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .sub-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }

    .glass-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 28px;
        transition: all 0.3s ease;
        position: relative;
    }

    .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; font-size: 1.2rem; }
    .stat-label { color: var(--text-dim); font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; }
    .stat-value { font-size: 2.2rem; font-weight: 800; margin: 8px 0; }
    .stat-footer { font-size: 0.75rem; color: var(--text-dim); display: flex; align-items: center; gap: 6px; }

    .progress-container { height: 6px; background: rgba(255,255,255,0.05); border-radius: 10px; margin: 15px 0; }
    .progress-bar { height: 100%; border-radius: 10px; background: var(--accent-primary); }

    .data-section { background: var(--panel-bg); backdrop-filter: blur(12px); border-radius: 24px; border: 1px solid var(--border-color); padding: 30px; }
    .premium-table { width: 100%; border-collapse: collapse; }
    .premium-table th { text-align: left; padding: 16px; color: var(--text-dim); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid var(--border-color); }
    .premium-table td { padding: 20px 16px; border-bottom: 1px solid rgba(255,255,255,0.03); }

    .badge { padding: 5px 12px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; }
    .badge-success { background: rgba(16, 185, 129, 0.1); color: var(--accent-success); }
    .badge-pink { background: rgba(217, 70, 239, 0.1); color: var(--accent-gem); }
    .badge-blue { background: rgba(59, 130, 246, 0.1); color: #60a5fa; }
    .badge-amber { background: rgba(251, 191, 36, 0.1); color: var(--accent-warning); }

    .roster-container { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .char-pill { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); padding: 2px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; white-space: nowrap; }
    .char-pill-gem { border-color: rgba(217, 70, 239, 0.3); color: var(--accent-gem); }
    .char-pill-cash { border-color: rgba(251, 191, 36, 0.3); color: var(--accent-warning); }

    .danger-zone {
        border: 2px dashed rgba(244, 63, 94, 0.3);
        padding: 40px;
        border-radius: 24px;
        text-align: center;
        background: rgba(244, 63, 94, 0.02);
    }
    .btn-reset-trigger {
        background: var(--accent-danger);
        color: white;
        border: none;
        padding: 14px 30px;
        border-radius: 12px;
        font-weight: 800;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.3s;
    }
    .btn-reset-trigger:hover { transform: scale(1.02); box-shadow: 0 0 20px rgba(244, 63, 94, 0.4); }
    
    .confirm-box {
        display: none;
        margin-top: 25px;
        animation: fadeIn 0.3s;
    }
    .reset-input {
        background: #000;
        border: 1px solid var(--border-color);
        color: var(--accent-danger);
        padding: 12px;
        border-radius: 8px;
        text-align: center;
        font-family: monospace;
        letter-spacing: 4px;
        margin-bottom: 15px;
        width: 200px;
    }
    .alert {
        padding: 15px 25px;
        border-radius: 12px;
        margin-bottom: 30px;
        font-weight: 600;
    }
    .alert-success { background: rgba(16, 185, 129, 0.15); color: var(--accent-success); border: 1px solid var(--accent-success); }
    .alert-error { background: rgba(244, 63, 94, 0.15); color: var(--accent-danger); border: 1px solid var(--accent-danger); }

    /* COUNTDOWN / LOADING STYLES */
    #loading-protocol { display: none; margin-top: 20px; }
    .protocol-label { font-weight: 800; color: var(--accent-danger); text-transform: uppercase; letter-spacing: 2px; }
    .protocol-timer { font-size: 2.5rem; font-weight: 800; margin: 10px 0; }
    .loading-bar-container { width: 100%; max-width: 400px; height: 10px; background: rgba(255,255,255,0.05); border-radius: 20px; margin: 20px auto; overflow: hidden; }
    .loading-bar-fill { height: 100%; width: 0%; background: var(--accent-danger); transition: width 1s linear; }
    .btn-abort { background: #fff; color: #000; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 800; cursor: pointer; margin-top: 10px; }

</style>

<div class="admin-wrapper">
    <?php if ($actionMessage): ?>
        <div class="alert alert-<?= $actionMessage['type'] ?>">
            <i class="fas fa-info-circle"></i> <?= $actionMessage['text'] ?>
        </div>
    <?php endif; ?>

    <header class="page-header">
        <div>
            <h2>Server Insight Engine</h2>
            <div class="text-dim text-sm mt-1">Real-time infrastructure and economic monitoring.</div>
        </div>
        <div class="tab-container">
            <button class="tab-btn active" onclick="switchTab('overview')">Overview</button>
            <button class="tab-btn" onclick="switchTab('forcegems')">Forcegem Ledger</button>
            <button class="tab-btn" onclick="switchTab('cash')">Cash Ledger</button>
            <button class="tab-btn tab-btn-danger" onclick="switchTab('admin')"><i class="fas fa-shield-alt"></i> Admin Operations</button>
        </div>
    </header>

    <div id="overview" class="tab-content active">
        <div class="top-stats-grid">
            <div class="glass-card">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--accent-success);"><i class="fas fa-satellite-dish"></i></div>
                <span class="stat-label">Live Connection</span>
                <div class="stat-value" style="color: var(--accent-success);"><?= number_format($num_onl) ?></div>
                <div class="progress-container"><div class="progress-bar" style="width: <?= min(100, $avg_online_pct) ?>%; background: var(--accent-success);"></div></div>
                <div class="stat-footer"><i class="fas fa-chart-line"></i> Engagement: <?= $avg_online_pct ?>%</div>
            </div>
            <div class="glass-card">
                <div class="stat-icon" style="background: rgba(129, 140, 248, 0.1); color: var(--accent-primary);"><i class="fas fa-user-plus"></i></div>
                <span class="stat-label">Daily Newcomers</span>
                <div class="stat-value"><?= number_format($daily_reg) ?></div>
                <div class="progress-container"><div class="progress-bar" style="width: 100%; opacity: 0.2;"></div></div>
                <div class="stat-footer" style="color: var(--accent-primary);"><i class="fas fa-clock"></i> Past 24 hours</div>
            </div>
            <div class="glass-card">
                <div class="stat-icon" style="background: rgba(251, 191, 36, 0.1); color: var(--accent-warning);"><i class="fas fa-users"></i></div>
                <span class="stat-label">Daily Avg Online</span>
                <div class="stat-value"><?= number_format($daily_avg_online) ?></div>
                <div class="progress-container"><div class="progress-bar" style="width: 100%; opacity: 0.2; background: var(--accent-warning);"></div></div>
                <div class="stat-footer"><i class="fas fa-calculator"></i> Rolling 24h Average</div>
            </div>
        </div>

        <div class="sub-stats-grid">
            <div class="glass-card" style="padding: 20px;"><span class="stat-label">Total Cash</span><div style="font-size: 1.4rem; font-weight: 800; margin-top: 5px; color: var(--accent-warning);"><?= number_format($cash_sum) ?></div></div>
            <div class="glass-card" style="padding: 20px;"><span class="stat-label">ForceGems</span><div style="font-size: 1.4rem; font-weight: 800; margin-top: 5px; color: var(--accent-gem);"><?= number_format($gem_sum) ?></div></div>
            <div class="glass-card" style="padding: 20px;"><span class="stat-label">Restricted</span><div style="font-size: 1.4rem; font-weight: 800; margin-top: 5px; color: var(--accent-danger);"><?= number_format($num_ban) ?></div></div>
            <div class="glass-card" style="padding: 20px;"><span class="stat-label">Characters</span><div style="font-size: 1.4rem; font-weight: 800; margin-top: 5px; color: #818cf8;"><?= number_format($num_cha) ?></div></div>
        </div>

        <div class="data-section">
            <h3 style="margin-bottom: 25px;"><i class="fas fa-database" style="color: var(--accent-primary); margin-right: 10px;"></i>Database Health</h3>
            <table class="premium-table">
                <thead><tr><th>Domain</th><th>Metric</th><th>Volume</th><th>Status</th></tr></thead>
                <tbody>
                    <tr><td>Account Table</td><td>Verified Identities</td><td><?= number_format($num_acc) ?></td><td><span class="badge badge-success">Active</span></td></tr>
                    <tr><td>Economy</td><td>Circulating Alz</td><td style="color: var(--accent-warning);"><?= number_format($char_alz_sum + $bank_sum) ?></td><td><span class="badge badge-blue">Monitored</span></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="forcegems" class="tab-content">
        <div class="data-section">
            <h3 style="margin-bottom: 25px;"><i class="fas fa-gem" style="color: var(--accent-gem); margin-right: 10px;"></i>Forcegem Leaders</h3>
            <table class="premium-table">
                <thead><tr><th>User Num</th><th>Identity / Roster</th><th style="text-align: right;">Amount</th><th style="text-align: center;">Rank</th></tr></thead>
                <tbody>
                    <?php foreach ($forcegem_list as $rank => $row): ?>
                    <tr>
                        <td style="font-family: monospace; color: var(--accent-primary);">#<?= $row['UserNum'] ?></td>
                        <td>
                            <div style="font-weight: 700; color: #fff;"><?= $row['UserID'] ?: 'Unknown' ?></div>
                            <div class="roster-container">
                                <?php if ($row['AllChars']): foreach (explode('|', $row['AllChars']) as $c): ?>
                                    <span class="char-pill char-pill-gem"><?= htmlspecialchars($c) ?></span>
                                <?php endforeach; else: echo '<small>Empty</small>'; endif; ?>
                            </div>
                        </td>
                        <td style="text-align: right; font-weight: 800; color: var(--accent-gem);"><?= number_format($row['ForcegemHave']) ?></td>
                        <td style="text-align: center;"><span class="badge <?= $rank < 3 ? 'badge-success' : 'badge-blue' ?>"><?= $rank + 1 ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="cash" class="tab-content">
        <div class="data-section">
            <h3 style="margin-bottom: 25px;"><i class="fas fa-coins" style="color: var(--accent-warning); margin-right: 10px;"></i>Cash Leaders</h3>
            <table class="premium-table">
                <thead><tr><th>User Num</th><th>Identity / Roster</th><th style="text-align: right;">Amount</th><th style="text-align: center;">Rank</th></tr></thead>
                <tbody>
                    <?php foreach ($cash_list as $rank => $row): ?>
                    <tr>
                        <td style="font-family: monospace; color: var(--accent-primary);">#<?= $row['UserNum'] ?></td>
                        <td>
                            <div style="font-weight: 700; color: #fff;"><?= $row['UserID'] ?: 'Unknown' ?></div>
                            <div class="roster-container">
                                <?php if ($row['AllChars']): foreach (explode('|', $row['AllChars']) as $c): ?>
                                    <span class="char-pill char-pill-cash"><?= htmlspecialchars($c) ?></span>
                                <?php endforeach; else: echo '<small>Empty</small>'; endif; ?>
                            </div>
                        </td>
                        <td style="text-align: right; font-weight: 800; color: var(--accent-warning);"><?= number_format($row['Cash']) ?></td>
                        <td style="text-align: center;"><span class="badge <?= $rank < 3 ? 'badge-amber' : 'badge-blue' ?>"><?= $rank + 1 ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="admin" class="tab-content">
        <div class="data-section">
            <h3 style="margin-bottom: 10px;"><i class="fas fa-exclamation-triangle" style="color: var(--accent-danger); margin-right: 10px;"></i>Danger Zone</h3>
            
            <div class="danger-zone">
                <div id="initial-ui">
                    <h4 style="font-size: 1.4rem; margin-bottom: 10px;">Full Server Database Reset</h4>
                    <p style="color: var(--text-dim); max-width: 600px; margin: 0 auto 30px auto;">
                        This will execute the <code style="color: var(--accent-danger);">usp_FullServerWipe</code> procedure. 
                        All characters, items, warehouses, and account progress will be permanently deleted.
                    </p>

                    <button class="btn-reset-trigger" id="resetInitBtn" onclick="toggleResetConfirm()">
                        <i class="fas fa-trash-alt"></i> Initialize Server Wipe
                    </button>

                    <form id="resetConfirmBox" class="confirm-box" method="POST" onsubmit="startWipeLoading(event)">
                        <input type="hidden" name="server_action" value="full_reset">
                        <p style="font-weight: 700; margin-bottom: 15px;">Type <span style="color: var(--accent-danger);">RESET</span> to confirm execution:</p>
                        <input type="text" name="confirm_code" id="wipe_code" class="reset-input" autocomplete="off" placeholder="----">
                        <br>
                        <button type="submit" class="btn-reset-trigger" style="background: #fff; color: #000; border: 2px solid #000;">
                            Finalize & Execute Wipe
                        </button>
                    </form>
                </div>

                <div id="loading-protocol">
                    <div class="protocol-label">Purge Sequence in Progress</div>
                    <div class="protocol-timer" id="timer-text">01:00</div>
                    <div class="loading-bar-container">
                        <div class="loading-bar-fill" id="bar-fill"></div>
                    </div>
                    <button class="btn-abort" onclick="abortWipe()">ABORT RESET</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let wipeCountdown;
    let seconds = 60;

    function switchTab(tabId) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        event.currentTarget.classList.add('active');
        document.getElementById(tabId).classList.add('active');
    }

    function toggleResetConfirm() {
        const btn = document.getElementById('resetInitBtn');
        const box = document.getElementById('resetConfirmBox');
        if (box.style.display === 'block') {
            box.style.display = 'none';
            btn.style.display = 'inline-block';
        } else {
            box.style.display = 'block';
            btn.style.display = 'none';
        }
    }

    function startWipeLoading(e) {
        e.preventDefault();
        if(document.getElementById('wipe_code').value !== 'RESET') {
            alert("Validation failed. Type RESET.");
            return;
        }

        document.getElementById('initial-ui').style.display = 'none';
        document.getElementById('loading-protocol').style.display = 'block';

        seconds = 60;
        wipeCountdown = setInterval(() => {
            seconds--;
            const m = Math.floor(seconds / 60).toString().padStart(2, '0');
            const s = (seconds % 60).toString().padStart(2, '0');
            document.getElementById('timer-text').innerText = `${m}:${s}`;
            
            const progress = ((60 - seconds) / 60) * 100;
            document.getElementById('bar-fill').style.width = progress + '%';

            if(seconds <= 0) {
                clearInterval(wipeCountdown);
                document.getElementById('resetConfirmBox').submit();
            }
        }, 1000);
    }

    function abortWipe() {
        if(confirm("Abort Database Purge?")) {
            clearInterval(wipeCountdown);
            document.getElementById('loading-protocol').style.display = 'none';
            document.getElementById('initial-ui').style.display = 'block';
            document.getElementById('resetConfirmBox').style.display = 'none';
            document.getElementById('resetInitBtn').style.display = 'inline-block';
            document.getElementById('wipe_code').value = '';
        }
    }
</script>