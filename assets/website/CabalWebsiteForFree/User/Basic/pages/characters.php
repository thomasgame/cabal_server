<?php
// --- 1. HANDLE POST ACTIONS (LOGIC) ---
$message = "";
$msgType = "";
$nationChangeCost = Database::NATION_CHANGE_COST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $charIdx = $_POST['char_idx'] ?? null;

    if (isset($_POST['change_nation']) && $charIdx) {
        $newNation = $_POST['nation_id'];
        
        $stmtChar = $conn->prepare("SELECT Login, Nation FROM Server01.dbo.cabal_character_table WHERE CharacterIdx = :idx");
        $stmtChar->execute(['idx' => $charIdx]);
        $charData = $stmtChar->fetch();

        if ($charData['Login'] != 0) {
            $message = "Character must be offline to change nation!";
            $msgType = "danger";
        } else {
            $isSwitch = ($charData['Nation'] != 0);
            $cost = $isSwitch ? $nationChangeCost : 0;

            $stmtCash = $conn->prepare("SELECT ForcegemHave FROM Server01.dbo.cabal_forcegem_table WHERE UserNum = :usernum");
            $stmtCash->execute(['usernum' => $UserNum]);
            $cash = $stmtCash->fetch();

            if ($isSwitch && (!$cash || $cash['ForcegemHave'] < $cost)) {
                $message = "Insufficient Gems! You need " . number_format($cost) . " gems to switch nations.";
                $msgType = "danger";
            } else {
                try {
                    $conn->beginTransaction();
                    if ($cost > 0) {
                        $deduct = $conn->prepare("EXEC Server01.dbo.AddFgems :cost, :usernum");
                        $deduct->execute(['cost' => -$cost, 'usernum' => $UserNum]);
                    }
                    $update = $conn->prepare("UPDATE Server01.dbo.cabal_character_table SET Nation = :nation WHERE CharacterIdx = :idx");
                    $update->execute(['nation' => $newNation, 'idx' => $charIdx]);
                    $conn->commit();
                    $message = "Nation updated successfully!" . ($cost > 0 ? " Charged " . number_format($cost) . " gems." : "");
                    $msgType = "success";
                } catch (Exception $e) {
                    $conn->rollBack();
                    $message = "Error: " . $e->getMessage();
                    $msgType = "danger";
                }
            }
        }
    }
}

// --- 2. FETCH DATA ---
$stmtChars = $conn->prepare("
    SELECT 
        c.CharacterIdx, c.Name, c.LEV, c.STR, c.DEX, c.[INT], c.Alz, c.Nation, c.Login,
        ((c.Style & 7) | (((c.Style >> 23) & 1) << 3)) AS CharacterClass,
        w.WarExp,
        low.WarPoint, low.KillCount,
        ovl.Level as OVL,
        myth.Level as MLV
    FROM Server01.dbo.cabal_character_table c
    LEFT JOIN Server01.dbo.cabal_WarExp_Table w ON c.CharacterIdx = w.CharacterIdx
    LEFT JOIN Server01.dbo.cabal_LordOfWar_point_table low ON c.CharacterIdx = low.CharacterIdx
    LEFT JOIN Server01.dbo.cabal_overload_table ovl ON c.CharacterIdx = ovl.CharacterIdx
    LEFT JOIN Server01.dbo.cabal_myth_table myth ON c.CharacterIdx = myth.CharacterIdx
    WHERE c.CharacterIdx / 16 = :usernum
");
$stmtChars->execute(['usernum' => $UserNum]);
$chars = $stmtChars->fetchAll();

if (!function_exists('getNationName')) {
    function getNationName($id) {
        switch($id) {
            case 1: return 'Capella';
            case 2: return 'Procyon';
            default: return 'Neutral';
        }
    }
}

if (!function_exists('getClassImage')) {
    function getClassImage($classId) {
        return "../../images/class/" . $classId . "_class.png";
    }
}

if (!function_exists('getIconImage')) {
    function getIconImage($classId) {
        return "../../images/class/" . $classId . ".png";
    }
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@400;700&family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">

<style>
    :root {
        --bg-main: #050505;
        --panel-bg: #0f0f12;
        --accent-primary: #a855f7; /* Violet */
        --accent-secondary: #ec4899; /* Pink */
        --accent-myth: #06b6d4; /* Cyan/Myth Color */
        --text-bright: #ffffff;
        --text-dim: #94a3b8;
        --glass-border: rgba(255, 255, 255, 0.08);
    }

    body {
        background-color: var(--bg-main);
        color: var(--text-bright);
        font-family: 'Space Grotesk', sans-serif;
    }

    .container { max-width: 1200px; margin: 50px auto; padding: 0 20px; }

    .chars-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 30px;
    }

    .char-card {
        background: var(--panel-bg);
        border: 1px solid var(--glass-border);
        border-radius: 4px;
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
    }

    .char-card:hover { border-color: var(--accent-primary); box-shadow: 0 0 20px rgba(168, 85, 247, 0.1); }

    .char-header {
        height: 220px;
        position: relative;
        background-size: cover;
        background-position: center;
    }

    .char-header::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom, transparent 40%, var(--panel-bg));
    }

    .class-icon-wrapper {
        position: absolute;
        top: 20px;
        left: 20px;
        z-index: 3;
        width: 45px;
        height: 45px;
        background: rgba(0,0,0,0.7);
        border: 1px solid var(--accent-primary);
        padding: 5px;
        backdrop-filter: blur(4px);
    }

    .class-icon-wrapper img { width: 100%; height: 100%; object-fit: contain; }

    .lvl-float {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 3;
        display: flex;
        flex-direction: column;
        gap: 4px;
        align-items: flex-end;
    }

    .lvl-tag {
        font-size: 10px;
        font-weight: 700;
        padding: 2px 10px;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .tag-base { background: var(--accent-primary); color: white; }
    .tag-ovl { background: var(--accent-secondary); color: white; }
    .tag-myth { background: var(--accent-myth); color: black; box-shadow: 0 0 10px var(--accent-myth); }

    .char-identity { position: absolute; bottom: 10px; left: 20px; z-index: 4; }
    .char-name { font-family: 'Syncopate', sans-serif; font-size: 18px; text-transform: uppercase; }

    .char-body { padding: 20px; }

    .stat-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-bottom: 20px;
    }

    .stat-item {
        background: rgba(255,255,255,0.03);
        padding: 8px;
        text-align: center;
        border: 1px solid var(--glass-border);
    }

    .stat-label { font-size: 9px; color: var(--text-dim); text-transform: uppercase; display: block; }
    .stat-value { font-weight: 700; font-size: 14px; }

    .war-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px; color: var(--text-dim); }
    .val-highlight { color: var(--text-bright); }

    .nation-ui { margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--glass-border); }
    .nation-btn-group { display: flex; gap: 5px; margin-top: 8px; }

    select { flex: 1; background: #16161a; border: 1px solid var(--glass-border); color: white; padding: 8px; font-size: 12px; outline: none; }
    .btn-set { background: var(--accent-primary); border: none; color: white; padding: 0 15px; font-weight: 700; font-size: 11px; cursor: pointer; }

    #action-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,0.95); display: none;
        flex-direction: column; align-items: center; justify-content: center; z-index: 1000;
    }
    #countdown-val { font-family: 'Syncopate', sans-serif; font-size: 100px; color: var(--accent-primary); }
</style>

<div class="container">
    <div id="action-overlay">
        <div style="font-size: 12px; letter-spacing: 5px; color: var(--text-dim); margin-bottom: 20px;">AUTHORIZING NATION CHANGE</div>
        <div id="countdown-val">5</div>
        <div style="margin-top: 30px; color: #444; cursor: pointer;" onclick="cancelAction()">[ ABORT ]</div>
    </div>

    <div class="chars-grid">
        <?php foreach ($chars as $char):
            $id = $char['CharacterIdx'];
            $isOnline = ($char['Login'] != 0);
        ?>
            <div class="char-card">
                <div class="char-header" style="background-image: url('<?= getClassImage($char['CharacterClass']) ?>');">
                    <div class="class-icon-wrapper">
                        <img src="<?= getIconImage($char['CharacterClass']) ?>" alt="Icon">
                    </div>

                    <div class="lvl-float">
                        <span class="lvl-tag tag-base">LVL <?= $char['LEV'] ?></span>
                        <?php if ($char['OVL'] > 0): ?>
                            <span class="lvl-tag tag-ovl">OVL <?= $char['OVL'] ?></span>
                        <?php endif; ?>
                        <?php if ($char['MLV'] > 0): ?>
                            <span class="lvl-tag tag-myth">MYTH <?= $char['MLV'] ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="char-identity">
                        <div class="char-name"><?= htmlspecialchars($char['Name']) ?></div>
                        <div style="font-size: 9px; text-transform: uppercase; color: <?= $isOnline ? '#22c55e' : '#475569' ?>;">
                            <?= $isOnline ? '● Online' : '○ Offline' ?>
                        </div>
                    </div>
                </div>

                <div class="char-body">
                    <div class="stat-row">
                        <div class="stat-item"><span class="stat-label">Str</span><span class="stat-value"><?= $char['STR'] ?></span></div>
                        <div class="stat-item"><span class="stat-label">Dex</span><span class="stat-value"><?= $char['DEX'] ?></span></div>
                        <div class="stat-item"><span class="stat-label">Int</span><span class="stat-value"><?= $char['INT'] ?></span></div>
                    </div>

                    <div class="war-info">
                        <div class="war-row"><span>Alz</span> <span class="val-highlight"><?= number_format($char['Alz']) ?></span></div>
                        <div class="war-row"><span>War Exp</span> <span class="val-highlight"><?= number_format($char['WarExp'] ?? 0) ?></span></div>
                        <div class="war-row"><span>Nation</span> <span class="val-highlight"><?= getNationName($char['Nation']) ?></span></div>
                    </div>

                    <form method="POST" id="form-<?= $id ?>" class="nation-ui">
                        <input type="hidden" name="char_idx" value="<?= $id ?>">
                        <input type="hidden" name="change_nation" id="submit-nation-<?= $id ?>" value="0" disabled>
                        
                        <div style="font-size: 9px; color: var(--accent-primary); font-weight: 700; text-transform: uppercase;">
                            <?= $char['Nation'] == 0 ? 'Select Nation' : 'Transfer ('.number_format($nationChangeCost).')' ?>
                        </div>
                        <div class="nation-btn-group">
                            <select name="nation_id" <?= $isOnline ? 'disabled' : '' ?>>
                                <option value="1" <?= $char['Nation'] == 1 ? 'selected' : '' ?>>Capella</option>
                                <option value="2" <?= $char['Nation'] == 2 ? 'selected' : '' ?>>Procyon</option>
                            </select>
                            <button type="button" class="btn-set" onclick="startNationChange(<?= $id ?>)" <?= $isOnline ? 'disabled' : '' ?>>GO</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    let timer = null;
    let activeId = null;

    function startNationChange(id) {
        activeId = id;
        document.getElementById('action-overlay').style.display = 'flex';
        let timeLeft = 5;
        document.getElementById('countdown-val').innerText = timeLeft;

        timer = setInterval(() => {
            timeLeft--;
            document.getElementById('countdown-val').innerText = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(timer);
                const input = document.getElementById('submit-nation-' + activeId);
                input.disabled = false;
                input.value = "1";
                document.getElementById('form-' + activeId).submit();
            }
        }, 1000);
    }

    function cancelAction() {
        if (timer) clearInterval(timer);
        document.getElementById('action-overlay').style.display = 'none';
        activeId = null;
    }
</script>