<?php
date_default_timezone_set('Asia/Manila');

// Assuming $conn is your PDO connection object
$message = $_GET['msg'] ?? "";
$messageType = "success";

// Determine active tab
$activeTab = $_POST['tab'] ?? ($_GET['tab'] ?? 'daily');

// --- CONSTANTS ---
$itemBinds = [
    "None" => 0,
    "Account Bind" => 4096,
    "Character Bind" => 524288,
    "Character Bind When Use" => 1572864,
    "Character Bind Extended" => 528384
];

// --- HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['admin_action'] ?? '';

    // 1. Daily Rewards (Updated to [Account].[dbo].[DailyLoginRewards])
    if (isset($_POST['save_daily'])) {
        $day = $_POST['day_number'];
        $iid = intval($_POST['item_id']) + intval($_POST['binding_val'] ?? 0);
        $iname = $_POST['item_name'];
        $iopt = $_POST['item_opt'];
        $dur = $_POST['duration'];

        // Check using DayNumber column
        $check = $conn->prepare("SELECT COUNT(*) FROM Account.dbo.DailyLoginRewards WHERE DayNumber = ?");
        $check->execute([$day]);

        if ($check->fetchColumn() > 0) {
            $stmt = $conn->prepare("UPDATE Account.dbo.DailyLoginRewards SET ItemID = ?, ItemName = ?, ItemOpt = ?, Duration = ? WHERE DayNumber = ?");
            $stmt->execute([$iid, $iname, $iopt, $dur, $day]);
        } else {
            $stmt = $conn->prepare("INSERT INTO Account.dbo.DailyLoginRewards (DayNumber, ItemID, ItemName, ItemOpt, Duration) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$day, $iid, $iname, $iopt, $dur]);
        }
        $message = "Daily Reward updated for Day $day.";
        $activeTab = 'daily';
    }

    // 2. Lucky Draw
    if (isset($_POST['add_lucky'])) {
        $stmt = $conn->prepare("INSERT INTO Account.dbo.OnlineRandomRewards (ItemID, ItemName, ItemOpt, Duration, IsActive) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$_POST['item_id'], $_POST['item_name'], $_POST['item_opt'], $_POST['duration']]);
        $message = "Lucky Draw item added.";
        $activeTab = 'lucky';
    }
    if (isset($_POST['del_lucky_item'])) {
        $stmt = $conn->prepare("DELETE FROM Account.dbo.OnlineRandomRewards WHERE RewardID = ?");
        $stmt->execute([$_POST['reward_id']]);
        $message = "Item removed from Lucky Draw.";
        $activeTab = 'lucky';
    }
}

// --- FETCH DATA ---
// Updated query for DailyLoginRewards table and column names
$dailyRewards = $conn->query("SELECT DayNumber, ItemName, ItemID, ItemOpt, Duration FROM Account.dbo.DailyLoginRewards ORDER BY DayNumber ASC")->fetchAll(PDO::FETCH_ASSOC);
$luckyPool = $conn->query("SELECT * FROM Account.dbo.OnlineRandomRewards ORDER BY ItemName ASC")->fetchAll(PDO::FETCH_ASSOC);
$recentWinners = $conn->query("SELECT TOP 50 * FROM Account.dbo.OnlineRewardLogs ORDER BY WonAt DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards Management Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap');
        * { font-family: 'Space Grotesk', sans-serif; }
        body { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); min-height: 100vh; color: white; }
        .glass-card { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px); }
        .gradient-border { position: relative; background: rgba(255, 255, 255, 0.03); border-radius: 16px; }
        .gradient-border::before {
            content: ''; position: absolute; inset: 0; border-radius: 16px; padding: 2px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b, #ec4899, #8b5cf6);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none;
        }
        .tab-active { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #000; font-weight: 600; }
        .tab-content { display: none; animation: fadeIn 0.3s ease-in; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .custom-scrollbar::-webkit-scrollbar { width: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #fbbf24, #f59e0b); border-radius: 10px; }
    </style>
</head>
<body class="p-4 md:p-8">

<div class="max-w-7xl mx-auto">
    <header class="mb-12">
        <div class="glass-card p-6 rounded-2xl flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center">
                    <i data-lucide="sparkles" class="w-8 h-8 text-white"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold">Rewards Portal</h1>
                    <p class="text-gray-400 text-sm">Daily Rewards & Lucky Draw</p>
                </div>
            </div>
            <div class="glass-card px-4 py-2 rounded-lg text-sm">
                <span class="text-gray-400">Server Time:</span>
                <span class="font-semibold ml-2"><?= date('H:i:s') ?></span>
            </div>
        </div>
    </header>

    <?php if ($message): ?>
        <div id="msg-banner" class="mb-8 glass-card p-4 rounded-xl flex justify-between items-center border-l-4 border-green-500">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle" class="text-green-400"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
            <button onclick="this.parentElement.remove()"><i data-lucide="x" class="w-5 h-5 text-gray-400"></i></button>
        </div>
    <?php endif; ?>

    <div class="glass-card rounded-2xl p-2 mb-8 flex gap-2 overflow-x-auto">
        <button onclick="switchTab('daily')" id="tab-daily" class="px-6 py-3 rounded-xl transition-all flex items-center gap-2">
            <i data-lucide="calendar" class="w-4 h-4"></i> Daily Rewards
        </button>
        <button onclick="switchTab('lucky')" id="tab-lucky" class="px-6 py-3 rounded-xl transition-all flex items-center gap-2">
            <i data-lucide="clover" class="w-4 h-4"></i> Lucky Draw
        </button>
        <button onclick="switchTab('logs')" id="tab-logs" class="px-6 py-3 rounded-xl transition-all flex items-center gap-2">
            <i data-lucide="activity" class="w-4 h-4"></i> Activity Logs
        </button>
    </div>

    <div id="content-daily" class="tab-content">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1">
                <div class="gradient-border p-6">
                    <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                        <i data-lucide="plus" class="text-blue-400"></i> Add/Edit Daily Reward
                    </h2>
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="tab" value="daily">
                        <div>
                            <label class="text-xs text-gray-400 uppercase">Day Number</label>
                            <input type="number" name="day_number" required class="w-full bg-white/5 border border-white/10 rounded-xl p-3 mt-1">
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 uppercase">Item ID</label>
                            <input type="number" name="item_id" required class="w-full bg-white/5 border border-white/10 rounded-xl p-3 mt-1">
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 uppercase">Item Name</label>
                            <input type="text" name="item_name" required class="w-full bg-white/5 border border-white/10 rounded-xl p-3 mt-1">
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 uppercase">Item Binding</label>
                            <select name="binding_val" class="w-full bg-white/5 border border-white/10 rounded-xl p-3 mt-1">
                                <?php foreach ($itemBinds as $k => $v): ?>
                                    <option value="<?= $v ?>"><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-gray-400 uppercase">Option</label>
                                <input type="number" name="item_opt" class="w-full bg-white/5 border border-white/10 rounded-xl p-3 mt-1">
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 uppercase">Duration</label>
                                <input type="number" name="duration" class="w-full bg-white/5 border border-white/10 rounded-xl p-3 mt-1">
                            </div>
                        </div>
                        <button type="submit" name="save_daily" class="w-full bg-gradient-to-r from-yellow-400 to-orange-500 text-black font-bold py-4 rounded-xl">
                            SAVE REWARD
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="lg:col-span-2 space-y-2 max-h-[600px] overflow-y-auto custom-scrollbar">
                <?php foreach ($dailyRewards as $r): ?>
                <div class="glass-card p-4 rounded-xl flex justify-between items-center group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-orange-500 flex items-center justify-center font-bold text-black"><?= $r['DayNumber'] ?></div>
                        <div>
                            <div class="font-semibold"><?= htmlspecialchars($r['ItemName']) ?></div>
                            <div class="text-xs text-gray-400">ID: <?= $r['ItemID'] ?> • Opt: <?= $r['ItemOpt'] ?> • Dur: <?= $r['Duration'] ?></div>
                        </div>
                    </div>
                    <button onclick='fillDaily(<?= json_encode($r) ?>)' class="p-2 rounded-lg bg-blue-500/20 text-blue-400"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div id="content-lucky" class="tab-content">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1">
                <div class="gradient-border p-6">
                    <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                        <i data-lucide="sparkles" class="text-green-400"></i> Add Lucky Item
                    </h2>
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="tab" value="lucky">
                        <input type="number" name="item_id" placeholder="Item ID" required class="w-full bg-white/5 border border-white/10 rounded-xl p-3">
                        <input type="text" name="item_name" placeholder="Item Name" required class="w-full bg-white/5 border border-white/10 rounded-xl p-3">
                        <div class="grid grid-cols-2 gap-4">
                            <input type="number" name="item_opt" placeholder="Option" class="w-full bg-white/5 border border-white/10 rounded-xl p-3">
                            <input type="number" name="duration" placeholder="Duration" class="w-full bg-white/5 border border-white/10 rounded-xl p-3">
                        </div>
                        <button type="submit" name="add_lucky" class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white font-bold py-4 rounded-xl">
                            ADD TO POOL
                        </button>
                    </form>
                </div>
            </div>
            <div class="lg:col-span-2 glass-card rounded-xl overflow-hidden">
                <table class="w-full">
                    <thead class="bg-white/5 text-left text-xs text-gray-400 uppercase">
                        <tr><th class="p-4">Item</th><th class="p-4">ID</th><th class="p-4 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($luckyPool as $p): ?>
                        <tr>
                            <td class="p-4"><?= htmlspecialchars($p['ItemName']) ?></td>
                            <td class="p-4 text-gray-400"><?= $p['ItemID'] ?></td>
                            <td class="p-4 text-right">
                                <form method="POST">
                                    <input type="hidden" name="tab" value="lucky"><input type="hidden" name="reward_id" value="<?= $p['RewardID'] ?>">
                                    <button type="submit" name="del_lucky_item" class="text-red-400 p-2 bg-red-500/10 rounded-lg"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="content-logs" class="tab-content">
        <div class="glass-card rounded-xl overflow-hidden">
            <div class="bg-white/5 p-4 border-b border-white/10 font-bold">Recent Winners</div>
            <table class="w-full text-left">
                <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                    <tr><th class="p-4">Character</th><th class="p-4">Reward</th><th class="p-4 text-right">Date</th></tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($recentWinners as $w): ?>
                    <tr>
                        <td class="p-4 font-semibold"><?= htmlspecialchars($w['CharacterName']) ?></td>
                        <td class="p-4 text-green-400"><?= htmlspecialchars($w['ItemName']) ?></td>
                        <td class="p-4 text-right text-gray-500 text-sm"><?= date('M d, H:i', strtotime($w['WonAt'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('button[id^="tab-"]').forEach(btn => btn.classList.remove('tab-active'));
        document.getElementById('content-' + tabId).classList.add('active');
        document.getElementById('tab-' + tabId).classList.add('tab-active');
        
        const url = new URL(window.location);
        url.searchParams.set('tab', tabId);
        window.history.replaceState({}, '', url);
        lucide.createIcons();
    }

    function fillDaily(data) {
        const form = document.querySelector('#content-daily form');
        const binds = <?= json_encode($itemBinds) ?>;
        let bindingValue = 0;
        let actualItemID = data.ItemID;
        
        for (const [name, value] of Object.entries(binds)) {
            if (value > 0 && data.ItemID >= value) {
                bindingValue = value;
                actualItemID = data.ItemID - value;
                break;
            }
        }
        
        form.querySelector('[name="day_number"]').value = data.DayNumber;
        form.querySelector('[name="item_id"]').value = actualItemID;
        form.querySelector('[name="item_name"]').value = data.ItemName;
        form.querySelector('[name="binding_val"]').value = bindingValue;
        form.querySelector('[name="item_opt"]').value = data.ItemOpt;
        form.querySelector('[name="duration"]').value = data.Duration;
    }

    window.onload = () => {
        switchTab('<?= $activeTab ?>');
        lucide.createIcons();
    };
</script>
</body>
</html>