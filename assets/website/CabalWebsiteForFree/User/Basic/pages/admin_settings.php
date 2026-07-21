<?php
/**
 * REYGIE ARCADE COMMAND CENTER - LITE
 * Focused: Plinko & Limbo Matrix
 */

// $conn initialization assumed
$msg = null;
$logEntries = [];
$siteDatabase = str_replace(']', ']]', DB_SITE);

function addLocalLog($message, $type = 'info') {
    global $logEntries;
    $time = date('H:i:s');
    $logEntries[] = ['time' => $time, 'msg' => $message, 'type' => $type];
}

// --- MASTER HANDLERS ---
if (isset($_POST['arcade_shutdown'])) {
    $conn->query("UPDATE [{$siteDatabase}].dbo.PlinkoSettings SET IsEnabled = 0");
    $conn->query("UPDATE [{$siteDatabase}].dbo.LimboSettings SET IsEnabled = 0");
    $msg = "ENGINES: EMERGENCY HALT SUCCESSFUL";
    addLocalLog($msg, 'error');
}

if (isset($_POST['arcade_restore'])) {
    $conn->query("UPDATE [{$siteDatabase}].dbo.PlinkoSettings SET IsEnabled = 1");
    $conn->query("UPDATE [{$siteDatabase}].dbo.LimboSettings SET IsEnabled = 1");
    $msg = "ENGINES: FULL RESTORATION COMPLETE";
    addLocalLog($msg, 'success');
}

// --- PLINKO HANDLER ---
if (isset($_POST['update_plinko'])) {
    $global_enabled = isset($_POST['plinko_global_enabled']) ? 1 : 0;
    foreach($_POST['rtp'] as $risk => $val) {
        $bias = $_POST['bias'][$risk];
        $stmt = $conn->prepare("UPDATE [{$siteDatabase}].dbo.PlinkoSettings SET RTP_Percentage = ?, HouseBias = ?, IsEnabled = ? WHERE RiskType = ?");
        $stmt->execute([$val, $bias, $global_enabled, $risk]);
    }
    $msg = "Plinko Matrix Updated";
    addLocalLog($msg, 'success');
}

// --- LIMBO HANDLER ---
if (isset($_POST['update_limbo'])) {
    $house_edge = (float)$_POST['limbo_house_edge'] / 100;
    $is_enabled = isset($_POST['limbo_enabled']) ? 1 : 0;
    $stmt = $conn->prepare("UPDATE [{$siteDatabase}].dbo.LimboSettings SET HouseEdge = ?, IsEnabled = ? WHERE SettingID = 1");
    $stmt->execute([$house_edge, $is_enabled]);
    $msg = "Limbo Physics Updated";
    addLocalLog($msg, 'success');
}

// --- FETCH STATE ---
try {
    $plinko_settings = $conn->query("SELECT * FROM [{$siteDatabase}].dbo.PlinkoSettings")->fetchAll(PDO::FETCH_ASSOC);
    $limbo_config = $conn->query("SELECT TOP 1 * FROM [{$siteDatabase}].dbo.LimboSettings")->fetch(PDO::FETCH_ASSOC);
    $plinko_global = (!empty($plinko_settings) && $plinko_settings[0]['IsEnabled']) ? 'checked' : '';
} catch (Exception $e) {
    $msg = "DB Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REYGIE COMMAND | Arcade Lite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #06080f; color: #e2e8f0; font-family: 'JetBrains Mono', monospace; }
        .glass-panel { background: rgba(13, 17, 23, 0.8); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.04); }
        
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .btn-shutdown { animation: pulse-red 2s infinite; }

        .switch-slider {
            position: relative; cursor: pointer; background-color: #1e293b;
            transition: 0.4s cubic-bezier(0.18, 0.89, 0.35, 1.15); border-radius: 34px;
        }
        .switch-slider:before {
            position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px;
            background-color: white; transition: 0.4s; border-radius: 50%;
        }
        .switch-input:checked + .switch-slider { background-color: #8b5cf6; }
        .switch-input:checked + .switch-slider:before { transform: translateX(14px); }
    </style>
</head>
<body class="p-4 md:p-10">

    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div>
                <h1 class="text-3xl font-black text-white tracking-tighter uppercase">Reygie Command</h1>
                <p class="text-[10px] text-purple-400 font-bold tracking-[0.3em] uppercase">Arcade Kernel V4.3 Lite</p>
            </div>
            
            <form method="POST" class="flex gap-4">
                <button type="submit" name="arcade_shutdown" class="btn-shutdown px-6 py-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-500 text-[9px] font-black uppercase tracking-widest">
                    <i class="fas fa-power-off mr-2"></i> EMERGENCY SHUTDOWN
                </button>
                <button type="submit" name="arcade_restore" class="px-6 py-3 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[9px] font-black uppercase tracking-widest">
                    <i class="fas fa-bolt mr-2"></i> SYSTEM RESTORE
                </button>
            </form>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- PLINKO MATRIX -->
            <div class="lg:col-span-2 glass-panel rounded-2xl p-8 border-emerald-500/10">
                <form method="POST">
                    <div class="flex justify-between items-center mb-8">
                        <div class="flex items-center gap-4 text-emerald-400">
                            <div class="p-3 rounded-xl bg-emerald-500/10"><i class="fas fa-braille text-xl"></i></div>
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-widest text-white">Plinko Matrix</h3>
                                <p class="text-[9px] text-slate-500 uppercase tracking-widest">Risk/Reward Logic</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="plinko_global_enabled" class="sr-only switch-input" <?= $plinko_global ?>>
                            <div class="w-9 h-5 switch-slider"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                        <?php foreach($plinko_settings as $risk): ?>
                        <div class="bg-black/30 p-4 rounded-2xl border border-white/5">
                            <span class="text-[9px] font-black text-emerald-500 uppercase tracking-widest mb-4 block"><?= $risk['RiskType'] ?> RISK</span>
                            <div class="space-y-4">
                                <div>
                                    <label class="text-[8px] uppercase text-slate-400 font-bold block mb-1">RTP (%)</label>
                                    <input type="number" name="rtp[<?= $risk['RiskType'] ?>]" value="<?= $risk['RTP_Percentage'] ?>" class="w-full bg-black/40 border border-white/5 p-2 rounded text-xs text-white outline-none focus:border-emerald-500/50">
                                </div>
                                <div>
                                    <label class="text-[8px] uppercase text-slate-400 font-bold block mb-1">House Bias</label>
                                    <input type="number" name="bias[<?= $risk['RiskType'] ?>]" value="<?= $risk['HouseBias'] ?>" class="w-full bg-black/40 border border-white/5 p-2 rounded text-xs text-white outline-none focus:border-emerald-500/50">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" name="update_plinko" class="w-full py-4 rounded-xl text-[10px] font-black uppercase bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg transition-all">Sync Plinko Matrix</button>
                </form>
            </div>

            <!-- LIMBO ENGINE -->
            <div class="glass-panel rounded-2xl p-8 border-rose-500/10">
                <form method="POST">
                    <div class="flex justify-between items-center mb-8">
                        <div class="flex items-center gap-4 text-rose-400">
                            <div class="p-3 rounded-xl bg-rose-500/10"><i class="fas fa-rocket text-xl"></i></div>
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-widest text-white">Limbo Engine</h3>
                                <p class="text-[9px] text-slate-500 uppercase tracking-widest">Multiplier Physics</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="limbo_enabled" class="sr-only switch-input" <?= ($limbo_config['IsEnabled']) ? 'checked' : '' ?>>
                            <div class="w-9 h-5 switch-slider"></div>
                        </label>
                    </div>

                    <div class="space-y-6 mb-8">
                        <div class="bg-black/30 p-6 rounded-2xl border border-white/5">
                            <label class="text-[9px] uppercase font-bold text-slate-400 block mb-3 tracking-widest">House Edge (%)</label>
                            <input type="number" step="0.01" name="limbo_house_edge" value="<?= $limbo_config['HouseEdge'] * 100 ?>" class="w-full bg-black/40 border border-white/5 p-4 rounded-xl text-white text-lg outline-none focus:border-rose-500/50">
                            <p class="text-[8px] text-slate-600 mt-4 italic font-mono uppercase">Applied to all outcome calculations</p>
                        </div>
                    </div>

                    <button type="submit" name="update_limbo" class="w-full py-4 rounded-xl text-[10px] font-black uppercase bg-rose-600 hover:bg-rose-500 text-white transition-all shadow-lg shadow-rose-600/10">Deploy Limbo Config</button>
                </form>
            </div>

        </div>

        <!-- Logs / Terminal Output -->
        <div class="mt-8 glass-panel rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <i class="fas fa-terminal text-purple-500 text-xs"></i>
                <h3 class="text-[9px] font-bold uppercase tracking-widest">Kernel Monitor</h3>
            </div>
            <div class="font-mono text-[10px] space-y-2 max-h-40 overflow-y-auto pr-4">
                <?php foreach(array_reverse($logEntries) as $log): ?>
                    <div class="flex gap-4 <?= ($log['type'] === 'success') ? 'text-emerald-400' : 'text-red-400' ?>">
                        <span class="text-slate-700">[<?= $log['time'] ?>]</span>
                        <span><?= $log['msg'] ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="text-slate-800 italic">>> Waiting for command input...</div>
            </div>
        </div>
    </div>

    <!-- Notification -->
    <?php if($msg): ?>
    <div id="toast" class="fixed bottom-10 right-10 z-[100] px-6 py-4 rounded-xl glass-panel border-white/10 text-white shadow-2xl flex items-center gap-4 transition-opacity duration-500">
        <div class="p-2 bg-purple-500/20 rounded-full text-purple-400"><i class="fas fa-info-circle"></i></div>
        <span class="text-[10px] font-bold uppercase tracking-widest"><?= $msg ?></span>
    </div>
    <script>setTimeout(() => document.getElementById('toast').style.opacity = '0', 3000);</script>
    <?php endif; ?>

</body>
</html>