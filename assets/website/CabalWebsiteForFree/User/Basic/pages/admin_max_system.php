<?php
/**
 * CABAL Max System Editor
 * Ported from MaxSystem Python implementation
 */

$current_page = $_GET['page'] ?? 'max_system';
$searchQuery = $_GET['search_name'] ?? '';
$searchResults = [];
$statusMessage = "";
$statusType = "";

// Helper to get UserNum from Character Name
function getUserNum($conn, $name) {
    $stmt = $conn->prepare("SELECT CharacterIdx / 16 as UserNum FROM Server01.dbo.cabal_character_table WHERE Name = ?");
    $stmt->execute([$name]);
    $res = $stmt->fetch();
    return $res ? $res['UserNum'] : null;
}

// --- HANDLE MAX ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['max_action'])) {
    $charName = $_POST['char_name'];
    $action = $_POST['max_action'];
    $userNum = getUserNum($conn, $charName);

    if (!$userNum) {
        $statusMessage = "Character not found.";
        $statusType = "danger";
    } else {
        try {
            switch ($action) {
                case 'max_souls_magic':
                    $passiveHex = "0xA40600000F0000002F0B000214000000000A000205000000A60900020A000000A009000214000000D90600020A000000940600020A000000930600020A0000009206000205000000910600020A00000090060002030000008F060002070000008E060002050000008D060002050000008C060002140000008B060002140000008A06000214000000890600021400000046090000050000004509000003000000A906000004000000A806000004000000A706000003000000A606000003000000A50600000F000000A30600000F000000A20600000F000000A10600000F000000A00600000F0000009F0600000F0000009E0600000A0000009D0600000A0000009C0600000A0000009B0600000A0000009A0600000A000000990600000A00000098060000140000009706000014000000940600000100000095060000010000009606000001000000500B0002050000009C0B000209000000A50300020A000000110B000209000000230A0002030000008E090002140000008D09000214000000A40300020A000000";
                    $blendedHex = "0x2407000000000000000000002507000000000000000000002007000000000000000000000907000000000000000000001607000000000000000000002107000000000000000000002207000000000000000000002307000000000000000000001C07000000000000000000001507000000000000000000001407000000000000000000000A07000000000000000000000C07000000000000000000000B07000000000000000000001B07000000000000000000001A0700000000000000000000080700000000000000000000FE0600000000000000000000FD0600000000000000000000FC0600000000000000000000";
                    $karmaHex = "0xE70D00020F00E60D00020500E50D00026400E80D00020F00";
                    
                    $stmt = $conn->prepare("UPDATE Server01.dbo.cabal_soul_ability_table 
                        SET PassiveAbilityData = $passiveHex, 
                            BlendedAbilityData = $blendedHex, 
                            KarmaAbilityData = $karmaHex,
                            BlendedSlotExtendedCount = 27, 
                            PassiveSlotExtendedCount = 44 
                        FROM Server01.dbo.cabal_soul_ability_table 
                        JOIN Server01.dbo.cabal_character_table ON Server01.dbo.cabal_character_table.CharacterIdx = Server01.dbo.cabal_soul_ability_table.CharacterIdx 
                        WHERE Server01.dbo.cabal_character_table.Name = ?");
                    $stmt->execute([$charName]);
                    $statusMessage = "Max Souls Runes (Magic) applied to $charName.";
                    break;

                case 'max_souls_sword':
                    $passiveHex = "0x880600020F00000089060002140000008B060002140000008C060002140000008F060002070024009206000205000000940600020A0000008E09000214000000000A000205000000230A000203000000110B000209000000A50300020A000000500B0002050000009C0B000209000000650000020500000046090000050000002F0B000214000000A60900020A000000A009000214000000D90600020A0000008D060002050000009006000203000000910600020A000000930600020A0000008A060002140000004509000003000000A806000004000000A9060000040000009F0600000F0000009E0600000A0000009D0600000A0000009C0600000A0000009B0600000A0000009A0600000A000000990600000A00000098060000140000009706000014000000960600000F000000950600000F000000940600000F000000930600000A000000920600000A000000910600000A000000900600000A0000008E06000205000000A706000003000000A606000003000000A50600000F000000A40600000F000000A30600000F000000A20600000F000000A10600000F000000A00600000F000000";
                    $blendedHex = "0x2407000000000000000000002507000000000000000000002007000000000000000000000907000000000000000000001607000000000000000000002107000000000000000000002207000000000000000000002307000000000000000000001C07000000000000000000001507000000000000000000001407000000000000000000000A07000000000000000000000C07000000000000000000000B07000000000000000000001B07000000000000000000001A0700000000000000000000080700000000000000000000FE0600000000000000000000FD0600000000000000000000FC0600000000000000000000";
                    $karmaHex = "0xE50D00026400E60D00020500E70D00020F00E80D00020F00";
                    
                    $stmt = $conn->prepare("UPDATE Server01.dbo.cabal_soul_ability_table 
                        SET PassiveAbilityData = $passiveHex, 
                            BlendedAbilityData = $blendedHex, 
                            KarmaAbilityData = $karmaHex,
                            BlendedSlotExtendedCount = 27, 
                            PassiveSlotExtendedCount = 44 
                        FROM Server01.dbo.cabal_soul_ability_table 
                        JOIN Server01.dbo.cabal_character_table ON Server01.dbo.cabal_character_table.CharacterIdx = Server01.dbo.cabal_soul_ability_table.CharacterIdx 
                        WHERE Server01.dbo.cabal_character_table.Name = ?");
                    $stmt->execute([$charName]);
                    $statusMessage = "Max Souls Runes (Sword) applied to $charName.";
                    break;

                case 'max_honor_medal':
                    $hexData = "0x0001000000002F000000010001010000002F000000010001020000002F000000010001030000002F000000010002000000000800000001000201000000080000000100020200000008000000010002030000000800000001000204000000080000000100020500000008000000010003000000007200000001000301000000720000000100030200000072000000010003030000007200000001000304000000720000000100030500000072000000010003060000007200000001000307000000720000000100040000000050000000010004010000005000000001000402000000500000000100040300000050000000010004040000005000000001000405000000500000000100040600000050000000010004070000005000000001000408000000500000000100040900000050000000010005000000008B000000010005010000008B000000010005020000008B000000010005030000008B000000010005040000008B000000010005050000008B000000010005060000008B000000010005070000008B000000010005080000008B000000010005090000008B0000000100050A0000008B0000000100050B0000008B00000001";
                    $conn->prepare("UPDATE Server01.dbo.cabal_honormedal_table SET [Mastery] = $hexData FROM Server01.dbo.cabal_honormedal_table JOIN Server01.dbo.cabal_character_table ON Server01.dbo.cabal_character_table.CharacterIdx = Server01.dbo.cabal_honormedal_table.CharacterIdx WHERE Server01.dbo.cabal_character_table.Name = ?")->execute([$charName]);
                    $conn->prepare("UPDATE Server01.dbo.cabal_honormedal_user_table SET grade = 50, score = 384001 WHERE UserNum = ?")->execute([$userNum]);
                    $statusMessage = "Max Honor Medal applied to $charName.";
                    break;

                case 'max_myth':
                    $masteryHex = "0x000128000002C9030003D70100042800000528000006C9030007C9030008D7010009D701000AE900000B4805000C8604000D9805000E8604000F70050010C30200112300001223000013ED030014ED030015ED030016ED03001705020018050200190B01001A5205001BAF04001CA205001DAF04001E7A05001FF10200206700002167000022150400231504002415040025150400263702002737020028280100295705002AD704002BAC05002CD704002D8405002E2303002F8F0000308F0000313D0400323D04003363020034630200358F00003661050037FF040038B6050039FF04003A8E05003B6302003C7D01003D7D01003E7D01003F630400407D0300417D0300427D0300437D010044250500457D030046BC000047BC000048630400496304004A9102004B9102004CA001004D3E05004EA103";
                    
                    $stmt = $conn->prepare("UPDATE Server01.dbo.cabal_myth_table 
                        SET [level] = 100, 
                            [Exp] = 13271475840412, 
                            [point] = 1000, 
                            [resetCount] = 106, 
                            [score] = 29400, 
                            [lockgroup] = 0x04, 
                            [mastery] = $masteryHex
                        FROM Server01.dbo.cabal_myth_table 
                        JOIN Server01.dbo.cabal_character_table ON Server01.dbo.cabal_character_table.CharacterIdx = Server01.dbo.cabal_myth_table.CharacterIdx 
                        WHERE Server01.dbo.cabal_character_table.Name = ?");
                    $stmt->execute([$charName]);
                    $statusMessage = "Max Myth Mastery applied to $charName.";
                    break;

                case 'max_stellar':
                    
                    
                    // Update Stellar System (Detailed Mastery Hex)
                    $systemHex = "0x01010005A3000000BA050000050000000F0000000100000001010105A3000000BA050000050000000F0000000100000001010205A3000000BA050000050000000F0000000100000001010305A3000000BA050000050000000F00000001000000010200059E000000BA050000050000000700000002000000010201059E000000BA050000050000000700000002000000010202059E000000BA050000050000000700000002000000010203059E000000BA050000050000000700000002000000010204059E000000BA050000050000000700000002000000010205059E000000BA0500000500000007000000020000000103000571000000BA0500000500000028000000010000000103010571000000BA0500000500000028000000010000000103020571000000BA0500000500000028000000010000000103030571000000BA0500000500000028000000010000000103040571000000BA0500000500000028000000010000000103050571000000BA0500000500000028000000010000000103060571000000BA0500000500000028000000010000000103070571000000BA0500000500000028000000010000000104000550000000BA050000050000000F000000010000000104010550000000BA050000050000000F000000010000000104020550000000BA050000050000000F000000010000000104030550000000BA050000050000000F000000010000000104040550000000BA050000050000000F000000010000000104050550000000BA050000050000000F000000010000000104060550000000BA050000050000000F000000010000000104070550000000BA050000050000000F000000010000000104080550000000BA050000050000000F000000010000000104090550000000BA050000050000000F000000010000000105000508000000BA0500000500000007000000020000000105010508000000BA0500000500000007000000020000000105020508000000BA0500000500000007000000020000000105030508000000BA0500000500000007000000020000000105040508000000BA0500000500000007000000020000000105050508000000BA0500000500000007000000020000000105060508000000BA0500000500000007000000020000000105070508000000BA0500000500000007000000020000000105080508000000BA0500000500000007000000020000000105090508000000BA05000005000000070000000200000001050A0508000000BA05000005000000070000000200000001050B0508000000BA050000050000000700000002000000";
                    $conn->prepare("UPDATE Server01.dbo.cabal_stellar_system_table SET mastery = $systemHex FROM Server01.dbo.cabal_stellar_system_table JOIN Server01.dbo.cabal_character_table ON Server01.dbo.cabal_character_table.CharacterIdx = Server01.dbo.cabal_stellar_system_table.CharacterIdx WHERE Server01.dbo.cabal_character_table.Name = ?")->execute([$charName]);
                    
                    $statusMessage = "Max Stellar Mastery applied to $charName.";
                    break;

                case 'max_overlord':
                    
                    
                    // 2. Update Overlord Table using your specific column names: Level, Exp, Point, Mastery
                    $overlordMasteryHex = "0x0105020503050405050506030705080509050A050B050C050D050E030F0510051105120513051405150516051705180519051A051B051C03";
                    
                    $stmt = $conn->prepare("UPDATE Server01.dbo.cabal_overload_table 
                        SET Exp = 12168740000000, 
                            Level = 100, 
                            Point = 0, 
                            Mastery = $overlordMasteryHex 
                        FROM Server01.dbo.cabal_overload_table 
                        JOIN Server01.dbo.cabal_character_table ON Server01.dbo.cabal_character_table.CharacterIdx = Server01.dbo.cabal_overload_table.CharacterIdx 
                        WHERE Server01.dbo.cabal_character_table.Name = ?");
                    $stmt->execute([$charName]);
                    
                    $statusMessage = "Max Overlord (Stats & Mastery) applied to $charName.";
                    break;

                case 'max_gold_merit':
                    // Update Merit Table using provided hex and points
                    $meritMasteryData = "0x010000000500000002000000050000000300000005000000040000000A000000050000000A000000060000000A000000070000000F000000080000000F000000090000000F0000000A000000010000000B000000010000000C000000010000000D000000010000000E000000010000000F00000001000000100000000500000011000000050000001200000005000000130000000A000000140000000A000000150000000A0000001600000005000000170000000F000000180000000F000000190000000F0000001A0000000A0000001B000000010000001C000000010000001D000000010000001E000000010000001F000000010000002000000001000000210000000500000022000000050000002300000005000000240000000A000000250000000A000000260000000A000000270000000F000000280000000F000000290000000F0000002A000000010000002B000000010000002C000000010000002D000000010000002E000000010000002F00000001000000300000000500000031000000050000003200000005000000330000000A000000340000000A000000350000000A0000003600000005000000370000000F000000380000000F000000390000000F0000003A0000000A0000003B000000010000003C000000010000003D000000010000003E000000010000003F000000010000004000000001000000";
                    $stmt = $conn->prepare("UPDATE Server01.dbo.cabal_merit_table 
                        SET meritpoint = 291010, 
                            MeritMasteryPoint = 0, 
                            MeritMasteryData = $meritMasteryData 
                        WHERE UserNum = ?");
                    $stmt->execute([$userNum]);
                    
                    $statusMessage = "Max Gold Merit applied to account for $charName.";
                    break;


                 case 'max_platinum_merit':
                    // Update Merit Platinum Table
                    $remainPointHex = "0x01000000000204080000030408000004040800000504080000";
                    $openSlotHex = "0x4100000042000000430000004400000045000000460000004700000048000000490000004A0000004B0000004C0000004D0000004E0000004F000000500000005100000052000000530000005400000055000000560000005700000058000000590000005A0000005B0000005C0000005D0000005E0000005F000000600000006100000062000000630000006400000065000000660000006700000068000000690000006A0000006B0000006C0000006D0000006E0000006F0000007000000071000000720000007300000074000000750000007600000077000000890000008A0000008B0000008C0000008D00000091000000920000009300000094000000950000009600000097000000980000009C0000009D0000009E00000010270000";
                    $specialMasteryHex = "0x0105000000020000000600000001050000000200000006000000010600000008000000060000000106000000080000000600000001070000000C0000000600000001070000000C0000000600000001080000001500000006000000010800000015000000060000000109000000190000000600000001090000001900000006000000010A0000001E00000006000000010A0000001E00000006000000";
                    
                    $conn->prepare("UPDATE Server01.dbo.cabal_merit_platinum_table 
                        SET HaveMeritPlatinumBagde = 1, 
                            MeritPlatinumPoint = 360542, 
                            MeritPlatinumPageExtendedCount = 0, 
                            MeritPlatinumMasteryPoint = 362000, 
                            UsingMeritPlatinumPageID = 1, 
                            MeritPlatinumMasteryOpenIndex = 0, 
                            MeritPlatinumMasteryOpenDate = '1970-01-01 01:00:00.000', 
                            MeritPlatinumMasteryRemainPoint = $remainPointHex, 
                            MeritPlatinumMasteryOpenSlot = $openSlotHex, 
                            MeritPlatinumSpecialMastery = $specialMasteryHex 
                        WHERE UserNum = ?")->execute([$userNum]);
                    
                    // Update Merit Platinum Mastery Table
                    $platinumMasteryHex = "0x01410000000500000001420000000300000001430000000A00000001440000000500000001450000000F00000001460000000A000000014700000005000000014800000005000000014900000005000000014A00000001000000014B00000001000000014C00000001000000014E0000000100000001500000000100000001510000000100000001520000000100000001530000000100000001540000000500000001550000000500000001560000000A00000001570000000A00000001580000000D00000001590000000F000000015C00000005000000015D00000001000000015E00000001000000015F00000001000000016000000001000000016100000001000000016200000001000000016500000001000000016600000001000000016700000005000000016800000003000000016900000003000000016A0000000A000000016B00000005000000016C00000005000000016D0000000F000000017000000001000000017100000001000000017200000001000000017300000001000000017400000001000000017500000001000000017600000001000000017700000001000000018900000003000000018A00000005000000018B00000005000000018C0000000A00000001910000000100000001920000000100000001940000000300000001950000000500000001960000000500000001970000000A00000001980000000A000000019C00000001000000019D00000001000000019E00000001000000";
                    $conn->prepare("UPDATE Server01.dbo.cabal_merit_platinum_mastery_table SET meritplatinummastery = $platinumMasteryHex WHERE UserNum = ?")->execute([$userNum]);

                    $statusMessage = "Max Platinum Merit applied to $charName.";
                    break;
            }
            $statusType = "success";
        } catch (PDOException $e) {
            $statusMessage = "SQL Error: " . $e->getMessage();
            $statusType = "danger";
        }
    }
}

// --- SEARCH LOGIC ---
if (!empty($searchQuery)) {
    $stmt = $conn->prepare("SELECT Name, CharacterIdx, LEV, CharacterClass FROM Server01.dbo.cabal_character_table WHERE Name LIKE ?");
    $stmt->execute(['%' . $searchQuery . '%']);
    $searchResults = $stmt->fetchAll();
}
?>

<style>
    :root {
        --bg-main: #020617; --bg-card: #0f172a; --bg-input: #1e293b;
        --text-dim: #94a3b8; --text-bright: #f8fafc;
        --accent: #38bdf8; --border: #334155;
    }
    .max-container { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-bright); padding: 40px 20px; min-height: 100vh; }
    .max-wrapper { max-width: 900px; margin: 0 auto; }
    .search-box { background: var(--bg-card); padding: 25px; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 30px; }
    .input-group { display: flex; gap: 10px; margin-top: 10px; }
    .input-field { flex: 1; background: var(--bg-input); border: 1px solid var(--border); padding: 12px 16px; color: white; border-radius: 8px; outline: none; }
    .btn-primary { background: var(--accent); color: #000; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; }
    
    .char-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-bottom: 30px; }
    .char-item { background: var(--bg-card); border: 1px solid var(--border); padding: 15px; border-radius: 12px; cursor: pointer; transition: all 0.2s; position: relative; }
    .char-item:hover { border-color: var(--accent); background: rgba(56, 189, 248, 0.05); }
    .char-item.selected { border-color: var(--accent); box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); }
    .char-item.selected::after { content: 'SELECTED'; position: absolute; top: 10px; right: 10px; font-size: 10px; background: var(--accent); color: #000; padding: 2px 6px; border-radius: 4px; font-weight: 800; }

    .options-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; opacity: 0.5; pointer-events: none; transition: opacity 0.3s; }
    .options-grid.active { opacity: 1; pointer-events: auto; }
    .btn-max { background: var(--bg-input); border: 1px solid var(--border); color: white; padding: 20px; border-radius: 12px; cursor: pointer; text-align: left; transition: all 0.2s; display: flex; flex-direction: column; gap: 4px; }
    .btn-max:hover { background: var(--border); transform: translateY(-2px); }
    .btn-max span { font-size: 0.8rem; color: var(--text-dim); }
    .btn-max strong { font-size: 1rem; color: var(--accent); }

    .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; text-align: center; }
    .alert-success { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid #10b981; }
    .alert-danger { background: rgba(244, 63, 94, 0.1); color: #f43f5e; border: 1px solid #f43f5e; }
</style>

<div class="max-container">
    <div class="max-wrapper">
        <header style="margin-bottom: 30px;">
            <h1 style="font-size: 2rem; margin-bottom: 8px;">Max System Editor</h1>
            <p style="color: var(--text-dim);">Select a character to unlock maximum progression stats instantly.</p>
        </header>

        <?php if ($statusMessage): ?>
            <div class="alert alert-<?= $statusType ?>"><?= $statusMessage ?></div>
        <?php endif; ?>

        <div class="search-box">
            <label style="font-size: 0.85rem; font-weight: 700; color: var(--accent);">STEP 1: FIND CHARACTER</label>
            <form method="GET" class="input-group">
                <input type="hidden" name="page" value="<?= $current_page ?>">
                <input type="text" name="search_name" class="input-field" placeholder="Enter Character Name..." value="<?= htmlspecialchars($searchQuery) ?>" required>
                <button type="submit" class="btn-primary">SEARCH</button>
            </form>
        </div>

        <?php if (!empty($searchResults)): ?>
            <div class="char-list">
                <?php foreach ($searchResults as $char): ?>
                    <div class="char-item" onclick="selectChar('<?= htmlspecialchars($char['Name']) ?>', this)">
                        <div style="font-weight: 700; font-size: 1.1rem;"><?= htmlspecialchars($char['Name']) ?></div>
                        <div style="color: var(--text-dim); font-size: 0.85rem;">Level <?= $char['LEV'] ?> • Class ID <?= $char['CharacterClass'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($searchQuery): ?>
            <p style="text-align:center; color: var(--text-dim);">No characters found.</p>
        <?php endif; ?>

        <div id="optionsPanel" class="options-grid">
            <form method="POST" id="maxForm" style="display:contents;">
                <input type="hidden" name="char_name" id="selectedCharName">
                
                <button type="submit" name="max_action" value="max_souls_magic" class="btn-max">
                    <span>Soul Ability</span>
                    <strong>Max Souls Runes Magic</strong>
                </button>

                <button type="submit" name="max_action" value="max_souls_sword" class="btn-max">
                    <span>Soul Ability</span>
                    <strong>Max Souls Runes Sword</strong>
                </button>

                <button type="submit" name="max_action" value="max_honor_medal" class="btn-max">
                    <span>Honor Medal</span>
                    <strong>Max Honor Medal</strong>
                </button>

                <button type="submit" name="max_action" value="max_myth" class="btn-max">
                    <span>Mythic System</span>
                    <strong>Max Myth Mastery</strong>
                </button>

                <button type="submit" name="max_action" value="max_stellar" class="btn-max">
                    <span>Stellar Link</span>
                    <strong>Max Stellar Stats</strong>
                </button>

                <button type="submit" name="max_action" value="max_overlord" class="btn-max">
                    <span>Overlord</span>
                    <strong>Max Overlord LV 100</strong>
                </button>

                <button type="submit" name="max_action" value="max_gold_merit" class="btn-max">
                    <span>Merit System</span>
                    <strong>Max Gold Merit</strong>
                </button>

                <button type="submit" name="max_action" value="max_platinum_merit" class="btn-max">
                    <span>Merit System</span>
                    <strong>Max Platinum Merit</strong>
                </button>
            </form>
        </div>

        <div id="instruction" style="text-align: center; margin-top: 40px; color: var(--text-dim); font-size: 0.9rem;">
            <i data-lucide="info" style="display: inline-block; width: 16px; vertical-align: middle; margin-right: 5px;"></i>
            Select a character above to enable the Max System options.
        </div>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();

    function selectChar(name, element) {
        // Update selection UI
        document.querySelectorAll('.char-item').forEach(el => el.classList.remove('selected'));
        element.classList.add('selected');

        // Update hidden field
        document.getElementById('selectedCharName').value = name;

        // Enable buttons
        const panel = document.getElementById('optionsPanel');
        panel.classList.add('active');
        
        // Hide instruction
        document.getElementById('instruction').style.display = 'none';

        // Scroll to panel
        panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Confirmation on click
    document.querySelectorAll('.btn-max').forEach(btn => {
        btn.onclick = (e) => {
            const action = btn.querySelector('strong').innerText;
            if (!confirm(`Are you sure you want to apply ${action}? This will overwrite existing data.`)) {
                e.preventDefault();
            }
        }
    });
</script>