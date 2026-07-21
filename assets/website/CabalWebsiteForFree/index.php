<?php
require('_connect/database.php');
require('_connect/_queries.php');

if (defined('WEB_MAINTENANCE') && WEB_MAINTENANCE == 1) {
    include("_connect/Maintenance.php");
    exit();
}

session_start();

if (!defined('TittleWeb')) define('TittleWeb', 'VOID ETHEREAL');
if (!defined('Title1')) define('Title1', 'CABAL');
if (!defined('Title2')) define('Title2', 'VOID');

$totalOnline = 0;
$topCharacters = $topGuilds = $topAlz = $topWexp = [];
$capellaBringers = $procyonBringers = [];
$srvrNation1 = $srvrNation2 = 0;
$channelCounts = [];
$channelLayers = [
    1 => ['name' => 'Channel 1 (Normal)', 'color' => '#06b6d4'],
    2 => ['name' => 'Channel 2 (Premium)', 'color' => '#fbbf24'],
    3 => ['name' => 'Channel 3 (Premium)', 'color' => '#fbbf24'],
    5 => ['name' => 'Channel 5 (Trade)', 'color' => '#a855f7'],
    16 => ['name' => 'Nation War', 'color' => '#f43f5e'],
];

try {
    // --- Live Online Count ---
    $stmtOnline = $db->prepare("SELECT COUNT(*) as OnlineCount FROM Server01.dbo.cabal_character_table WHERE Login = 1");
    $stmtOnline->execute();
    $onlineRow = $stmtOnline->fetch(PDO::FETCH_ASSOC);
    $totalOnline = $onlineRow['OnlineCount'] ?? 0;

    // Nation counts for progress bars
    $stmtN1 = $db->prepare("SELECT COUNT(*) FROM Server01.dbo.cabal_character_table WHERE Nation = 1");
    $stmtN1->execute();
    $srvrNation1 = $stmtN1->fetchColumn() ?: 0;

    $stmtN2 = $db->prepare("SELECT COUNT(*) FROM Server01.dbo.cabal_character_table WHERE Nation = 2");
    $stmtN2->execute();
    $srvrNation2 = $stmtN2->fetchColumn() ?: 0;

    // --- Data Fetching for Rankings ---
    $topCharacters = getTopCharacters($db, 10);
    $topGuilds = getTopGuilds($db, 10);
    $topAlz = getTopAlz($db, 10);
    $topWexp = getTopWexp($db, 10);

    // --- Bringer Rankings (Nation Leaders) ---
    $sqlBringers = "SELECT L.Name, L.Nation, L.LordType,
                           ((C.Style & 7) | (((C.Style >> 23) & 1) << 3)) AS CharacterClass,
                           C.Lev
                    FROM Server01.dbo.cabal_LordOfWar_table L
                    LEFT JOIN Server01.dbo.cabal_character_table C ON L.Name = C.Name
                    ORDER BY L.LordType ASC";
    $stmtBringers = $db->prepare($sqlBringers);
    $stmtBringers->execute();
    $bringersData = $stmtBringers->fetchAll(PDO::FETCH_ASSOC);

    $capellaBringers = array_filter($bringersData, function($b) { return $b['Nation'] == 1; });
    $procyonBringers = array_filter($bringersData, function($b) { return $b['Nation'] == 2; });

    // --- Channel Status ---
    $channelCounts = [];
    $sqlChannels = "SELECT ChannelIdx, COUNT(*) as Count FROM Server01.dbo.cabal_character_table WHERE Login = 1 GROUP BY ChannelIdx";
    $stmtChannels = $db->prepare($sqlChannels);
    $stmtChannels->execute();
    while ($row = $stmtChannels->fetch(PDO::FETCH_ASSOC)) {
        $channelCounts[$row['ChannelIdx']] = $row['Count'];
    }

} catch (Exception $e) {
    error_log("Homepage data query failed: " . $e->getMessage());
}

function ClassStyle($CharacterClass) {
    $classes = [
        1 => ['name' => 'WA'], 2 => ['name' => 'BL'], 3 => ['name' => 'WI'], 4 => ['name' => 'FA'],
        5 => ['name' => 'FS'], 6 => ['name' => 'FB'], 7 => ['name' => 'GL'], 8 => ['name' => 'FG'], 9 => ['name' => 'DM'],
    ];
    $c = isset($classes[$CharacterClass]) ? $classes[$CharacterClass] : ['name' => '??'];
    return "<div class='flex items-center gap-2 justify-center'><img src='images/class/{$CharacterClass}.png' alt='{$c['name']}' class='w-5 h-5 object-contain' onerror=\"this.style.display='none'\"> <span class='text-[10px] font-bold text-slate-400'>{$c['name']}</span></div>";
}

function maskUsername($username) {
    return substr($username, 0, 2) . '****' . substr($username, -2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= TittleWeb ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --v-indigo: #6366f1; --v-cyan: #22d3ee; --v-dark: #020617; --v-border: rgba(255,255,255,0.08); }
        body { background-color: var(--v-dark); color: #f8fafc; font-family: 'Rajdhani', sans-serif; overflow-x: hidden; }
        .hero-bg { position: fixed; inset: 0; z-index: -1; background: linear-gradient(to bottom, rgba(2, 6, 23, 0.8), rgba(2, 6, 23, 1)), url('../../images/image3.jpg'); background-size: cover; background-position: center; }
        .ethereal-glow { text-shadow: 0 0 20px rgba(34, 211, 238, 0.5), 0 0 40px rgba(34, 211, 238, 0.2); font-family: 'Orbitron', sans-serif; }
        .cyber-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid var(--v-border); }
        .bar-track { background: rgba(0, 0, 0, 0.4); height: 5px; border-radius: 4px; overflow: hidden; }
        .bar-inner { height: 100%; transition: width 1.5s ease; }
        .tab-btn.active { color: var(--v-cyan); border-bottom: 2px solid var(--v-cyan); background: rgba(34, 211, 238, 0.05); }
        .btn-primary { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4); transition: all 0.3s ease; }
        .nation-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        @media (max-width: 768px) { .nation-grid { grid-template-columns: 1fr; } }
        .modal-overlay { background: rgba(2, 6, 23, 0.85); backdrop-filter: blur(8px); transition: opacity 0.3s ease; }
        .modal-content { transform: scale(0.95); transition: transform 0.3s ease; }
        .modal-active .modal-content { transform: scale(1); }
    </style>
</head>
<body class="antialiased">
    <div class="hero-bg"></div>

    <!-- Reusable Modal System -->
    <div id="modalContainer" class="fixed inset-0 z-[200] hidden items-center justify-center p-4 modal-overlay">
        <div class="cyber-card max-w-lg w-full rounded-3xl overflow-hidden modal-content border border-cyan-500/30">
            <div class="flex justify-between items-center p-6 border-b border-white/5 bg-white/5">
                <h3 id="modalTitle" class="text-xl font-black text-white uppercase tracking-tighter italic">System</h3>
                <button onclick="closeModal()" class="text-slate-500 hover:text-white transition-colors"><i data-lucide="x" class="w-6 h-6"></i></button>
            </div>
            <div id="modalBody" class="p-8"></div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-[100] bg-black/60 backdrop-blur-xl border-b border-white/5 h-20 flex items-center">
        <div class="max-w-7xl mx-auto px-6 w-full flex justify-between items-center">
            <div class="flex items-center gap-4 cursor-pointer" onclick="window.location.href='index.php'">
                <img src="images/logo.png" class="w-10 h-10 object-contain" onerror="this.src='https://placehold.co/40x40/06b6d4/white?text=V'">
                <div class="flex flex-col leading-none">
                    <span class="text-xl font-black text-white tracking-tighter"><?= Title1 ?> <span class="text-cyan-400"><?= Title2 ?></span></span>
                    <span class="text-[9px] uppercase tracking-widest text-slate-500 font-bold">Bringer of Light</span>
                </div>
            </div>
            <div class="hidden md:flex gap-8 text-[11px] font-bold uppercase tracking-widest text-slate-300">
                <a href="index.php" class="hover:text-cyan-400 transition-colors">Home</a>
                <button onclick="openModal('download')" class="hover:text-cyan-400 transition-colors uppercase">Download</button>
                <button onclick="openModal('register')" class="hover:text-cyan-400 transition-colors uppercase">Register</button>
                <a href="#" class="hover:text-cyan-400 transition-colors">Market</a>
            </div>
            <div>
                <?php if(isset($_SESSION['UserNum'])): ?>
                    <div class="flex items-center gap-4">
                        <span class="text-xs font-bold text-cyan-400 uppercase">Hi, <?= $_SESSION['Username'] ?></span>
                        <a href="User/Basic/dashboard.php" class="bg-white/5 border border-white/10 text-white px-4 py-1.5 uppercase font-bold text-[9px] hover:bg-cyan-500/20 transition-all rounded">Dashboard</a>
                    </div>
                <?php else: ?>
                    <button onclick="openModal('login')" class="bg-white/5 border border-white/10 text-white px-6 py-2 uppercase font-bold text-[10px] tracking-widest hover:bg-cyan-500 hover:border-cyan-500 transition-all rounded">Account Login</button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="relative z-10">
        <!-- Hero Section & Channel Status -->
        <section class="min-h-screen flex flex-col items-center justify-center text-center px-4 pt-32 pb-20">
            <div class="max-w-5xl w-full">
                <div class="inline-block px-4 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 text-[10px] font-bold uppercase tracking-[0.2em] mb-8">The Ultimate Private Server</div>
                <h1 class="text-5xl md:text-8xl font-black text-white mb-6 uppercase tracking-tighter leading-none ethereal-glow">CABAL <br> <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-indigo-500">ORIGIN PH</span></h1>
                <p class="text-lg md:text-xl text-slate-400 mb-12 max-w-2xl mx-auto font-medium leading-relaxed">Unleash your true power in the most advanced Cabal Private Server. Experience flawless combat and balanced gameplay.</p>
                
                <div class="flex flex-col sm:flex-row justify-center items-center gap-6 mb-24">
                    <button onclick="openModal('download')" class="btn-primary px-12 py-5 rounded-2xl font-black tracking-widest uppercase text-white flex items-center gap-3 shadow-lg hover:scale-105 transition-transform"><i data-lucide="play-circle" class="w-6 h-6"></i> Start Adventure</button>
                    <div class="cyber-card px-8 py-4 rounded-2xl flex items-center gap-4 border-white/10">
                        <div class="text-left">
                            <div class="flex items-center gap-2"><span class="text-3xl font-bold text-white"><?= number_format($totalOnline) ?></span><span class="w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse shadow-[0_0_10px_#22c55e]"></span></div>
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-[0.1em]">Active Players</span>
                        </div>
                    </div>
                </div>

                <!-- Channel Status Restoration -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <?php foreach ($channelLayers as $id => $data): 
                        $count = isset($channelCounts[$id]) ? $channelCounts[$id] : 0;
                        $percent = min(($count / 100) * 100, 100); ?>
                    <div class="cyber-card p-4 rounded-xl text-left border-t-2" style="border-top-color: <?= $data['color'] ?>;">
                        <div class="flex flex-col mb-3">
                            <span class="text-[10px] font-black uppercase text-white tracking-tighter mb-1"><?= $data['name'] ?></span>
                            <div class="flex justify-between items-center text-[9px] font-bold">
                                <span class="text-slate-500"><?= $count ?> ONLINE</span>
                                <span style="color: <?= $data['color'] ?>;"><?= round($percent) ?>%</span>
                            </div>
                        </div>
                        <div class="bar-track"><div class="bar-inner" style="width: <?= $percent ?>%; background: <?= $data['color'] ?>;"></div></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Nation Rankings Restoration -->
        <section class="max-w-7xl mx-auto px-6 pb-32">
            <div class="mb-24">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-black text-white uppercase tracking-tighter italic ethereal-glow">The Eternal Conflict</h2>
                    <p class="text-slate-500 text-xs uppercase tracking-[0.3em]">Current Nation Bringers</p>
                </div>

                <div class="nation-grid">
                    <!-- Capella -->
                    <div class="cyber-card rounded-3xl overflow-hidden border-t-4 border-blue-500">
                        <div class="bg-blue-500/10 p-4 border-b border-white/5 flex justify-between items-center">
                            <span class="text-blue-400 font-black uppercase tracking-widest text-sm">Capella Sage</span>
                            <img src="images/server-icon_1.png" class="w-12 h-12 object-contain" onerror="this.src='https://placehold.co/30x30/3b82f6/white?text=C'">
                        </div>
                        <table class="w-full text-left text-sm">
                            <tbody class="divide-y divide-white/5">
                                <?php foreach($capellaBringers as $b): ?>
                                <tr class="hover:bg-blue-500/5 transition-colors">
                                    <td class="p-4">
                                        <div class="font-bold text-white"><?= htmlspecialchars($b['Name']) ?></div>
                                        <div class="flex items-center gap-1 mt-1"><?= ClassStyle($b['CharacterClass']) ?> <span class="text-[9px] text-slate-500">Lv.<?= $b['Lev'] ?></span></div>
                                    </td>
                                    <td class="p-4 text-right">
                                        <span class="px-3 py-1 rounded text-[9px] font-black uppercase tracking-tighter <?= $b['LordType'] == 1 ? 'bg-blue-600 text-white shadow-[0_0_10px_rgba(59,130,246,0.5)]' : 'border border-blue-500/50 text-blue-400' ?>">
                                            <?= $b['LordType'] == 1 ? 'Storm Bringer' : 'Guardian' ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Procyon -->
                    <div class="cyber-card rounded-3xl overflow-hidden border-t-4 border-red-500">
                        <div class="bg-red-500/10 p-4 border-b border-white/5 flex justify-between items-center">
                            <span class="text-red-400 font-black uppercase tracking-widest text-sm">Procyon Knight</span>
                            <img src="images/server-icon_2.png" class="w-12 h-12 object-contain" onerror="this.src='https://placehold.co/30x30/ef4444/white?text=P'">
                        </div>
                        <table class="w-full text-left text-sm">
                            <tbody class="divide-y divide-white/5">
                                <?php foreach($procyonBringers as $b): ?>
                                <tr class="hover:bg-red-500/5 transition-colors">
                                    <td class="p-4">
                                        <div class="font-bold text-white"><?= htmlspecialchars($b['Name']) ?></div>
                                        <div class="flex items-center gap-1 mt-1"><?= ClassStyle($b['CharacterClass']) ?> <span class="text-[9px] text-slate-500">Lv.<?= $b['Lev'] ?></span></div>
                                    </td>
                                    <td class="p-4 text-right">
                                        <span class="px-3 py-1 rounded text-[9px] font-black uppercase tracking-tighter <?= $b['LordType'] == 1 ? 'bg-red-600 text-white shadow-[0_0_10px_rgba(239,68,68,0.5)]' : 'border border-red-500/50 text-red-400' ?>">
                                            <?= $b['LordType'] == 1 ? 'Storm Bringer' : 'Guardian' ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Hall of Fame Restoration -->
            <div>
                <div class="flex flex-col md:flex-row justify-between items-end gap-4 mb-8">
                    <div>
                        <h2 class="text-4xl font-black text-white uppercase tracking-tighter">Hall of Fame</h2>
                        <p class="text-slate-500 text-sm">Global Rankings & Statistics</p>
                    </div>
                    <div class="flex gap-2 p-1.5 bg-white/5 rounded-xl border border-white/10 overflow-x-auto">
                        <button onclick="switchTab('charRank')" id="btn-charRank" class="tab-btn active px-4 py-2 text-[10px] font-bold uppercase tracking-widest transition-all">Characters</button>
                        <button onclick="switchTab('guildRank')" id="btn-guildRank" class="tab-btn px-4 py-2 text-[10px] font-bold uppercase tracking-widest transition-all">Guilds</button>
                        <button onclick="switchTab('wexpRank')" id="btn-wexpRank" class="tab-btn px-4 py-2 text-[10px] font-bold uppercase tracking-widest transition-all">War Exp</button>
                        <button onclick="switchTab('alzRank')" id="btn-alzRank" class="tab-btn px-4 py-2 text-[10px] font-bold uppercase tracking-widest transition-all">Wealth</button>
                    </div>
                </div>

                <div class="cyber-card rounded-3xl overflow-hidden">
                    <!-- Char Ranking -->
                    <div id="charRank" class="tab-content overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-[10px] uppercase text-slate-500 font-bold border-b border-white/5">
                                <tr><th class="p-6 text-center w-20">#</th><th class="p-6">Character</th><th class="p-6 text-center">Class</th><th class="p-6 text-right">Reputation</th></tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php $r=1; foreach($topCharacters as $row): ?>
                                <tr class="hover:bg-white/5">
                                    <td class="p-6 text-center text-slate-600 font-black"><?= sprintf("%02d", $r++) ?></td>
                                    <td class="p-6 font-bold text-white"><?= htmlspecialchars($row['Name']) ?></td>
                                    <td class="p-6 text-center"><?= ClassStyle($row['CharacterClass']) ?></td>
                                    <td class="p-6 text-right text-emerald-400 font-mono font-bold"><?= number_format($row['Reputation']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Guild Ranking -->
                    <div id="guildRank" class="tab-content hidden overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-[10px] uppercase text-slate-500 font-bold border-b border-white/5">
                                <tr><th class="p-6 text-center w-20">#</th><th class="p-6">Guild</th><th class="p-6 text-center">Level</th><th class="p-6 text-right">Points</th></tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php $r=1; foreach($topGuilds as $row): ?>
                                <tr class="hover:bg-white/5">
                                    <td class="p-6 text-center text-slate-600 font-black"><?= sprintf("%02d", $r++) ?></td>
                                    <td class="p-6 font-bold text-white uppercase italic tracking-tighter"><?= htmlspecialchars($row['GuildName']) ?></td>
                                    <td class="p-6 text-center"><span class="bg-indigo-500/20 text-indigo-400 px-3 py-1 rounded-md text-[10px] font-bold">LVL <?= $row['Level'] ?></span></td>
                                    <td class="p-6 text-right text-yellow-500 font-mono font-bold"><?= number_format($row['Point']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- War Exp Ranking -->
                    <div id="wexpRank" class="tab-content hidden overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-[10px] uppercase text-slate-500 font-bold border-b border-white/5">
                                <tr><th class="p-6 text-center w-20">#</th><th class="p-6">Warrior</th><th class="p-6 text-right">War Exp</th></tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php $r=1; foreach($topWexp as $row): ?>
                                <tr class="hover:bg-white/5">
                                    <td class="p-6 text-center text-slate-600 font-black"><?= sprintf("%02d", $r++) ?></td>
                                    <td class="p-6 font-bold text-white"><?= htmlspecialchars($row['Name']) ?></td>
                                    <td class="p-6 text-right text-red-500 font-mono font-bold"><?= number_format($row['WarExp']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Alz Ranking -->
                    <div id="alzRank" class="tab-content hidden overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-[10px] uppercase text-slate-500 font-bold border-b border-white/5">
                                <tr><th class="p-6 text-center w-20">#</th><th class="p-6">Account</th><th class="p-6 text-right">Total Alz</th></tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php $r=1; foreach($topAlz as $row): ?>
                                <tr class="hover:bg-white/5">
                                    <td class="p-6 text-center text-slate-600 font-black"><?= sprintf("%02d", $r++) ?></td>
                                    <td class="p-6 font-mono text-slate-400"><?= maskUsername($row['AccountID']) ?></td>
                                    <td class="p-6 text-right text-emerald-500 font-mono font-bold"><?= number_format($row['TotalAlz']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-black/90 border-t border-white/5 py-16 text-center">
        <div class="max-w-4xl mx-auto px-6">
            <h3 class="text-2xl font-black text-white mb-4 uppercase italic tracking-tighter"><?= Title1 ?> <span class="text-cyan-400"><?= Title2 ?></span></h3>
            <p class="text-[10px] uppercase font-bold text-slate-700 tracking-[0.4em]">© <?= date('Y') ?> Void Ethereal Gaming</p>
        </div>
    </footer>

   <script>
    lucide.createIcons();

    // System Initialization
    let procyonPlayers = <?= (int)$srvrNation2 ?>;
    let capellaPlayers = <?= (int)$srvrNation1 ?>;

    // --- Modal System ---
    function openModal(type) {
        const container = document.getElementById('modalContainer');
        const title = document.getElementById('modalTitle');
        const body = document.getElementById('modalBody');
        
        container.classList.remove('hidden');
        container.classList.add('flex');
        setTimeout(() => container.classList.add('modal-active'), 10);

        if (type === 'login') {
            title.innerText = 'Account Authentication';
            body.innerHTML = `
                <form id="loginForm" class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Account ID</label>
                        <input type="text" name="username" required class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-cyan-500 outline-none transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Password</label>
                        <input type="password" name="password" required class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-cyan-500 outline-none transition-all">
                    </div>
                    <button type="submit" class="btn-primary w-full py-4 rounded-xl font-black uppercase tracking-widest text-xs mt-4">Sign In</button>
                </form>
            `;
            attachLoginHandler();
        } 
        else if (type === 'register') {
            title.innerText = 'Join the War';
            body.innerHTML = `
                <form id="registerForm" class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Desired Username</label>
                        <input type="text" name="reg_username" required maxlength="16" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-cyan-500 outline-none transition-all" placeholder="Enter ID...">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Secure Password</label>
                        <input type="password" name="reg_password" required class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-cyan-500 outline-none transition-all" placeholder="••••••••">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">E-mail Address</label>
                        <input type="email" name="reg_email" required class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-cyan-500 outline-none transition-all" placeholder="email@example.com">
                    </div>
                    <button type="submit" class="btn-primary w-full py-4 rounded-xl font-black uppercase tracking-widest text-xs mt-4">Create Account</button>
                </form>
            `;
            attachRegisterHandler();
        } 
        else if (type === 'download') {
            title.innerText = 'Deployment Center';
            body.innerHTML = `
                <div class="space-y-3">
                    <p class="text-slate-400 text-xs mb-4">Choose a download mirror for the Full Client:</p>
                    <a href="#" class="block p-4 bg-white/5 border border-white/10 rounded-xl hover:border-cyan-500 transition-all text-sm font-bold text-white text-center">Mirror 1: Google Drive</a>
                    <a href="#" class="block p-4 bg-white/5 border border-white/10 rounded-xl hover:border-cyan-500 transition-all text-sm font-bold text-white text-center">Mirror 2: MEGA.NZ</a>
                </div>
            `;
        }
        lucide.createIcons();
    }

    function closeModal() {
        const container = document.getElementById('modalContainer');
        container.classList.remove('modal-active');
        setTimeout(() => { container.classList.add('hidden'); container.classList.remove('flex'); }, 300);
    }

    // --- Registration Logic ---
    function attachRegisterHandler() {
        const regForm = document.getElementById("registerForm");
        if(!regForm) return;

        regForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const formData = new URLSearchParams(new FormData(this));

            Swal.fire({ 
                title: "Processing...", 
                text: "Creating your account",
                allowOutsideClick: false, 
                didOpen: () => Swal.showLoading() 
            });

            fetch("api/register.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                Swal.fire({
                    icon: data.success ? "success" : "error",
                    title: data.success ? "Success!" : "Registration Failed",
                    text: data.message,
                    confirmButtonColor: "#06b6d4"
                }).then(() => {
                    if (data.success) closeModal();
                });
            })
            .catch(err => {
                Swal.fire({ icon: "error", title: "Connection Error", text: "Could not reach the server." });
            });
        });
    }

    // --- Login Logic ---
    function attachLoginHandler() {
        const loginForm = document.getElementById("loginForm");
        if(!loginForm) return;

        loginForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const formData = new URLSearchParams(new FormData(this));

            Swal.fire({ title: "Connecting...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            fetch("login.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                Swal.fire({
                    icon: data.success ? "success" : "error",
                    title: data.message,
                    confirmButtonColor: "#06b6d4"
                }).then(() => {
                    if (data.success) window.location.href = "User/Basic/dashboard.php";
                });
            })
            .catch(err => {
                Swal.fire({ icon: "error", title: "Login Connection Failed." });
            });
        });
    }

    function switchTab(target) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(target).classList.remove('hidden');
        document.getElementById('btn-' + target).classList.add('active');
    }

    window.onclick = (e) => { if (e.target == document.getElementById('modalContainer')) closeModal(); }
</script>
</body>
</html>