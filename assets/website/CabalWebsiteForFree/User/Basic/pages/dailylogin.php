<?php
/**
 * Daily Login Rewards (Cash Inventory) & Online Random Reward (Event Inventory)
 * Includes logic for auto-cleanup, playtime tracking, and automated rewards.
 */

$message = "";
$msgType = "";
$requiredPlaytime = 120; // Minutes

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');
$currentTime = time();
$tomorrowMidnight = strtotime('tomorrow midnight');
$secondsRemaining = $tomorrowMidnight - $currentTime;

// --- 0. AUTO-DELETE LOGS (24-Hour Cleanup) ---
$cleanupInterval = 86400; 
$lastCleanupFile = 'last_log_cleanup.txt';
$lastCleanupTime = file_exists($lastCleanupFile) ? (int)file_get_contents($lastCleanupFile) : 0;

if (($currentTime - $lastCleanupTime) >= $cleanupInterval) {
    try {
        $stmtCleanup = $conn->prepare("DELETE FROM Account.dbo.OnlineRewardLogs WHERE WonAt < DATEADD(hour, -24, GETDATE())");
        $stmtCleanup->execute();
        file_put_contents($lastCleanupFile, $currentTime);
    } catch (Exception $e) {
        // Silently fail if DB error
    }
}

// --- 1. USER PROGRESS & PLAYTIME ---
$stmt = $conn->prepare("SELECT * FROM Account.dbo.DailyUserProgress WHERE UserNum = :un");
$stmt->execute(['un' => $UserNum]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$progress) {
    $conn->prepare("INSERT INTO Account.dbo.DailyUserProgress (UserNum, CurrentStreak, LastClaimDate, TotalClaims, LastPlayTimeSnapshot) VALUES (:un, 0, NULL, 0, 0)")->execute(['un' => $UserNum]);
    $progress = ['CurrentStreak' => 0, 'LastClaimDate' => null, 'TotalClaims' => 0, 'LastPlayTimeSnapshot' => 0];
}

$stmtAuth = $conn->prepare("SELECT PlayTime FROM Account.dbo.cabal_auth_table WHERE UserNum = :un");
$stmtAuth->execute(['un' => $UserNum]);
$authData = $stmtAuth->fetch(PDO::FETCH_ASSOC);
$totalCumulativePlaytime = (int)($authData['PlayTime'] ?? 0);

$todaysPlaytime = $totalCumulativePlaytime - $progress['LastPlayTimeSnapshot'];
$alreadyClaimed = ($progress['LastClaimDate'] === $today);
$hasEnoughTime = ($todaysPlaytime >= $requiredPlaytime);
$canClaim = (!$alreadyClaimed && $hasEnoughTime);

// --- 2. ONLINE LUCKY DRAW LOGIC (Event Inventory Delivery) ---
$rewardInterval = 8640; 
$lastRewardFile = 'last_random_reward.txt'; 
$lastTime = file_exists($lastRewardFile) ? (int)file_get_contents($lastRewardFile) : 0;

if (($currentTime - $lastTime) >= $rewardInterval) {
    $stmtOnline = $conn->query("SELECT TOP 1 UserNum FROM Account.dbo.cabal_auth_table WHERE Login = 1 ORDER BY NEWID()");
    $luckyWinner = $stmtOnline->fetch(PDO::FETCH_ASSOC);

    if ($luckyWinner) {
        $winnerUn = $luckyWinner['UserNum'];
        
        $stmtPool = $conn->query("SELECT TOP 1 ItemID, ItemName, ItemOpt, Duration FROM Account.dbo.OnlineRandomRewards WHERE IsActive = 1 ORDER BY NEWID()");
        $randomReward = $stmtPool->fetch(PDO::FETCH_ASSOC);

        $stmtChar = $conn->prepare("SELECT TOP 1 Name FROM Server01.dbo.cabal_character_table WHERE CharacterIdx / 16 = :un ORDER BY Lev DESC");
        $stmtChar->execute(['un' => $winnerUn]);
        $charData = $stmtChar->fetch(PDO::FETCH_ASSOC);

        if ($charData && $randomReward) {
            try {
                // Delivery to Event Inventory (Stored Procedure)
                $stmtEvent = $conn->prepare("EXEC Server01.dbo.cabal_sp_event_inventory_reward :un, :iid, :iopt, :dur, 1, 7, 1");
                $stmtEvent->execute([
                    'un'   => $winnerUn, 
                    'iid'  => $randomReward['ItemID'], 
                    'iopt' => $randomReward['ItemOpt'], 
                    'dur'  => $randomReward['Duration']
                ]);

                // Log the win
                $logStmt = $conn->prepare("INSERT INTO Account.dbo.OnlineRewardLogs (UserNum, CharacterName, ItemName, WonAt) VALUES (:un, :cn, :in, GETDATE())");
                $logStmt->execute(['un' => $winnerUn, 'cn' => $charData['Name'], 'in' => $randomReward['ItemName']]);

                file_put_contents($lastRewardFile, $currentTime);
            } catch (Exception $e) {
                // Silently fail
            }
        }
    }
}

// --- 3. FETCH UI DATA ---
$recentWinners = $conn->query("SELECT TOP 10 * FROM Account.dbo.OnlineRewardLogs ORDER BY WonAt DESC")->fetchAll(PDO::FETCH_ASSOC);
$rewardsList = $conn->query("SELECT * FROM Account.dbo.DailyLoginRewards ORDER BY DayNumber ASC")->fetchAll(PDO::FETCH_ASSOC);
// Fetch the possible pool for the Lucky Draw
$poolList = $conn->query("SELECT ItemID, ItemName, Duration FROM Account.dbo.OnlineRandomRewards WHERE IsActive = 1")->fetchAll(PDO::FETCH_ASSOC);

// --- 4. HANDLE CLAIM ACTION (Daily Login - Cash Inventory) ---
if (isset($_POST['claim_daily']) && $canClaim) {
    try {
        $conn->beginTransaction();
        $nextDay = ($progress['CurrentStreak'] >= 7) ? 1 : $progress['CurrentStreak'] + 1;
        
        $stmtReward = $conn->prepare("SELECT * FROM Account.dbo.DailyLoginRewards WHERE DayNumber = :dn");
        $stmtReward->execute(['dn' => $nextDay]);
        $reward = $stmtReward->fetch(PDO::FETCH_ASSOC);

        if (!$reward) throw new Exception("Reward data missing.");

        // Deliver to Cash Inventory
        $stmtItem = $conn->prepare("EXEC CabalCash.dbo.up_AddMyCashItemByItem :usernum, 1, 1, :itemid, :itemopt, :duration");
        $stmtItem->execute([
            'usernum'  => $UserNum, 
            'itemid'   => $reward['ItemID'], 
            'itemopt'  => $reward['ItemOpt'], 
            'duration' => $reward['Duration']
        ]);

        // Update Progress
        $upd = $conn->prepare("UPDATE Account.dbo.DailyUserProgress SET CurrentStreak = :cs, LastClaimDate = :lcd, TotalClaims = TotalClaims + 1, LastPlayTimeSnapshot = :snap WHERE UserNum = :un");
        $upd->execute(['cs' => $nextDay, 'lcd' => $today, 'snap' => $totalCumulativePlaytime, 'un' => $UserNum]);

        $conn->commit();
        $message = "Success! Day $nextDay reward added to your Cash Inventory.";
        $msgType = "success";
        $alreadyClaimed = true;
    } catch (Exception $e) {
        $conn->rollBack();
        $message = "Error: " . $e->getMessage();
        $msgType = "danger";
    }
}
?>

<style>
    :root {
        --bg-card: #0f1115;
        --bg-item: #1c1f26;
        --accent-blue: #3b82f6;
        --accent-gold: #ffcc00;
        --accent-green: #10b981;
        --text-main: #f3f4f6;
        --text-dim: #9ca3af;
        --card-border: #2d333f;
    }

    .daily-container {
        max-width: 950px;
        margin: 40px auto;
        display: flex;
        flex-direction: column;
        gap: 20px;
        font-family: 'Inter', sans-serif;
        color: var(--text-main);
    }

    .reward-section {
        background: var(--bg-card);
        border-radius: 20px;
        border: 1px solid var(--card-border);
        padding: 30px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.4);
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--card-border);
    }

    .section-title h3 { margin: 0; text-transform: uppercase; letter-spacing: 2px; font-size: 1.1rem; }

    .daily-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
        gap: 12px;
        margin-bottom: 25px;
    }

    .day-card {
        background: var(--bg-item);
        border-radius: 12px;
        padding: 15px 10px;
        text-align: center;
        border: 1px solid var(--card-border);
        transition: 0.3s;
    }
    .day-card.claimed { border-color: var(--accent-blue); opacity: 0.5; filter: grayscale(1); }
    .day-card.current { border-color: var(--accent-gold); background: #232731; transform: scale(1.05); }

    .item-img-container { width: 50px; height: 50px; margin: 10px auto; background: rgba(0,0,0,0.2); border-radius: 8px; display: flex; align-items: center; justify-content: center; position: relative; }
    .item-img { max-width: 80%; max-height: 80%; object-fit: contain; }

    .btn-claim {
        background: linear-gradient(135deg, var(--accent-gold) 0%, #d9ac00 100%);
        color: #000;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 800;
        cursor: pointer;
        width: 100%;
        max-width: 300px;
        text-transform: uppercase;
    }
    .btn-claim:disabled { background: #232731; color: #4b5563; cursor: not-allowed; }

    .random-reward-box {
        display: flex;
        align-items: center;
        gap: 25px;
        background: rgba(16, 185, 129, 0.05);
        border: 1px dashed var(--accent-green);
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 20px;
    }

    .lucky-tag { background: var(--accent-green); color: #000; font-size: 0.6rem; font-weight: 900; padding: 2px 6px; border-radius: 4px; }
    .timer-pill { background: rgba(0,0,0,0.3); padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; color: var(--accent-gold); border: 1px solid rgba(255,204,0,0.2); }

    /* Pool Styling */
    .pool-container {
        background: rgba(0,0,0,0.2);
        padding: 15px;
        border-radius: 12px;
        margin-top: 15px;
    }
    .pool-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 10px;
        max-height: 200px;
        overflow-y: auto;
        padding-right: 5px;
    }
    .pool-grid::-webkit-scrollbar { width: 4px; }
    .pool-grid::-webkit-scrollbar-thumb { background: var(--card-border); border-radius: 10px; }
    
    .pool-item {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--bg-item);
        padding: 8px;
        border-radius: 8px;
        border: 1px solid var(--card-border);
    }
    .pool-item-img { width: 30px; height: 30px; border-radius: 4px; background: #000; display: flex; align-items: center; justify-content: center; }

    .transparency-note {
        margin-top: 20px;
        padding: 15px;
        background: rgba(0, 0, 0, 0.4);
        border-radius: 10px;
        font-size: 0.75rem;
        line-height: 1.5;
        color: var(--text-dim);
        border-left: 3px solid var(--accent-green);
    }
</style>

<div class="daily-container">
    
    <!-- SECTION 1: DAILY LOGIN -->
    <div class="reward-section">
        <div class="section-title">
            <i data-lucide="calendar-check" style="color: var(--accent-gold);"></i>
            <h3 style="color: var(--accent-gold);">Daily Login Expedition</h3>
            <div style="flex:1"></div>
            <div class="timer-pill">Reset: <span id="reset-timer">--:--:--</span></div>
        </div>

        <?php if ($message): ?>
            <div style="padding: 12px; border-radius: 8px; margin-bottom: 20px; background: rgba(0,0,0,0.3); color: <?= $msgType === 'success' ? '#10b981' : '#ef4444' ?>; border: 1px solid currentColor;">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="daily-grid">
            <?php foreach ($rewardsList as $row): 
                $day = $row['DayNumber'];
                $isClaimed = ($day <= $progress['CurrentStreak']);
                $isCurrent = ($day == $progress['CurrentStreak'] + 1 && !$alreadyClaimed);
            ?>
                <div class="day-card <?= $isClaimed ? 'claimed' : ($isCurrent ? 'current' : '') ?>">
                    <div style="font-size: 0.6rem; color: var(--text-dim); font-weight: 900;">DAY <?= $day ?></div>
                    <div class="item-img-container">
                        <img src="../../images/items/<?= $row['ItemID'] ?>.gif" class="item-img" onerror="this.src='https://via.placeholder.com/30/1c1f26/ffcc00?text=?'">
                    </div>
                    <div style="font-size: 0.65rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding: 0 5px;"><?= htmlspecialchars($row['ItemName']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center;">
            <form method="POST">
                <?php if ($alreadyClaimed): ?>
                    <button type="button" class="btn-claim" disabled>CLAIMED FOR TODAY</button>
                <?php elseif (!$hasEnoughTime): ?>
                    <button type="button" class="btn-claim" disabled>LOCKED (<?= $requiredPlaytime - $todaysPlaytime ?>M LEFT)</button>
                <?php else: ?>
                    <button type="submit" name="claim_daily" class="btn-claim">CLAIM REWARD</button>
                <?php endif; ?>
            </form>
            <p style="font-size: 0.7rem; color: var(--text-dim); margin-top: 10px;">Requirement: <?= $requiredPlaytime ?> Minutes Daily Playtime. Rewards go to <strong>Cash Inventory</strong>.</p>
        </div>
    </div>

    <!-- SECTION 2: RANDOM ONLINE REWARD -->
    <div class="reward-section" style="border-color: rgba(16, 185, 129, 0.3);">
        <div class="section-title">
            <i data-lucide="sparkles" style="color: var(--accent-green);"></i>
            <h3 style="color: var(--accent-green);">Online Lucky Draw</h3>
            <div style="flex:1"></div>
            <span style="font-size: 0.7rem; color: var(--text-dim);">Automated Selection</span>
        </div>

        <!-- RECENT WINNERS -->
        <div class="random-reward-box">
            <div style="text-align: center; border-right: 1px solid var(--card-border); padding-right: 20px;">
                <div class="item-img-container" style="border: 1px solid var(--accent-green);">
                    <i data-lucide="gift" style="color: var(--accent-green); width: 24px; height: 24px;"></i>
                </div>
                <div style="font-size: 0.6rem; font-weight: 800; margin-top: 5px; color: var(--accent-green);">EVENT INV</div>
            </div>
            
            <div style="flex: 1; overflow: hidden;">
                <div style="margin-bottom: 8px;"><span class="lucky-tag">RECENT WINNERS</span></div>
                <?php if (!empty($recentWinners)): ?>
                    <marquee scrollamount="3" style="color: var(--accent-green); font-size: 0.85rem; font-weight: 500;">
                        <?php foreach ($recentWinners as $win): ?>
                            🎉 <strong style="color: #fff;"><?= htmlspecialchars($win['CharacterName']) ?></strong> received [<?= htmlspecialchars($win['ItemName']) ?>] &nbsp;&nbsp;&bull;&nbsp;&nbsp;
                        <?php endforeach; ?>
                    </marquee>
                <?php else: ?>
                    <p style="font-size: 0.75rem; color: var(--text-dim); margin: 0;">Analyzing active energy... Next winner soon!</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- REWARD POOL LIST -->
        <div class="pool-container">
            <h4 style="font-size: 0.75rem; color: var(--accent-green); margin-bottom: 12px; display: flex; align-items: center; gap: 5px;">
                <i data-lucide="list" style="width: 14px;"></i> POSSIBLE LOOT POOL
            </h4>
            <div class="pool-grid">
                <?php foreach ($poolList as $pool): ?>
                    <div class="pool-item">
                        <div class="pool-item-img">
                            <img src="../../images/items/<?= $pool['ItemID'] ?>.jpg" style="max-width: 40px;" onerror="this.src='https://via.placeholder.com/20/000/10b981?text=?'">
                        </div>
                        <div style="overflow: hidden;">
                            <div style="font-size: 0.7rem; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($pool['ItemName']) ?></div>
                            <div style="font-size: 0.6rem; color: var(--text-dim);"><?= $pool['Duration'] > 0 ? ($pool['Duration'] / 86400).' Days' : 'Permanent' ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <section class="transparency-note">
            <h4 style="margin-top:0;"><i data-lucide="info" style="width: 16px; vertical-align: bottom;"></i> DIVINE FAIR PLAY</h4>
            <p>
                <b>System Transparency:</b> This system is 100% automated. The winner is selected purely by a <b>random database query</b> targeting players currently logged in. 
                Rewards are distributed to the <b>Event Inventory</b>. Only active accounts are eligible. 
                GMs and staff are excluded from the raffle logic.
            </p>
        </section>
    </div>

</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
    let timeLeft = <?= $secondsRemaining ?>;
    const timerDisplay = document.getElementById('reset-timer');

    function updateTimer() {
        if (timeLeft <= 0) {
            timerDisplay.innerHTML = "RESETTING...";
            setTimeout(() => location.reload(), 2000);
            return;
        }
        const hours = Math.floor(timeLeft / 3600);
        const mins = Math.floor((timeLeft % 3600) / 60);
        const secs = timeLeft % 60;
        timerDisplay.innerHTML = `${hours}h ${mins}m ${secs}s`;
        timeLeft--;
    }
    setInterval(updateTimer, 1000);
    updateTimer();
</script>