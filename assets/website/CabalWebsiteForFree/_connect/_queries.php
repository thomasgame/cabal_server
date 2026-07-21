<?php
require_once('_connect/database.php');
$conn = new Database;
$db = $conn->getConnection();
include('settings.php');



function getCounts($db) {
    try {
        $CountAcc = $db->prepare("SELECT COUNT(*) FROM ".DB_ACC.".dbo.cabal_auth_table");
        $CountAcc->execute();
        $CountAccTotal = $CountAcc->fetchColumn();
        unset($CountAcc);

        $CountCI = $db->prepare("SELECT count(*) FROM ".DB_GAME.".dbo.cabal_character_table");
        $CountCI->execute();
        $CountCITotal = $CountCI->fetchColumn();
        unset($CountCI);

        $CountOnline = $db->prepare("SELECT count(*) FROM ".DB_GAME.".dbo.cabal_character_table WHERE Login = 1 AND Nation <> 3");
        $CountOnline->execute();
        $CountOnlineTotal = $CountOnline->fetchColumn();
        unset($CountOnline);

        $ActivePlayer = $db->prepare("SELECT count(*) FROM ".DB_ACC.".dbo.cabal_auth_table WHERE DATEDIFF(DAY,[LoginTime],CURRENT_TIMESTAMP) = 0");
        $ActivePlayer->execute();
        $ActivePlayerTotal = $ActivePlayer->fetchColumn();
        unset($ActivePlayer);

        return [
            'accounts' => $CountAccTotal,
            'characters' => $CountCITotal,
            'online' => $CountOnlineTotal,
            'activeToday' => $ActivePlayerTotal
        ];
    } catch(PDOException $e) {
        throw new Exception("Error getting counts: ".$e->getMessage());
    }
}

function getTopCharacters($db, $limit = 9) {
    // We sum AttackPoint + DefencePoint as 'CombatPower'
    $stmt = $db->prepare("
        SELECT TOP $limit 
            c.Name, c.LEV, c.Reputation, c.PlayTime, c.Nation, c.Login,
            ((c.Style & 7) | (((c.Style >> 23) & 1) << 3)) AS CharacterClass,
            o.Level AS Olv, w.Grade AS WingGrade, w.Level AS WingLevel, m.Level AS MythLevel,
            (cp.AttackPoint + cp.DefencePoint) AS CombatPower
        FROM ".DB_GAME.".dbo.cabal_character_table c
        LEFT JOIN ".DB_GAME.".dbo.cabal_overload_table o ON c.CharacterIdx = o.CharacterIdx
        LEFT JOIN ".DB_GAME.".dbo.cabal_forcewing_table w ON c.CharacterIdx = w.CharIdx
        LEFT JOIN ".DB_GAME.".dbo.cabal_myth_table m ON c.CharacterIdx = m.CharacterIdx
        LEFT JOIN Server01.dbo.cabal_combat_params_table cp ON c.CharacterIdx = cp.CharacterIdx
        WHERE c.Nation <> 3
        ORDER BY CombatPower DESC, c.PlayTime DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getTopGuilds($db, $limit = 5) {
    $stmt = $db->prepare("
        SELECT TOP $limit 
            g.GuildName, g.GuildUrl, g.GuildNo, g2.Point, g2.Level,
            COUNT(*) AS MEMB,
            (SELECT TOP 1 Name FROM ".DB_GAME.".dbo.cabal_character_table WHERE CharacterIdx = (SELECT TOP 1 CharacterIndex FROM ".DB_GAME.".dbo.GuildMember AS gm2 WHERE gm2.GuildNo = g.GuildNo ORDER BY GroupIndex, RegDate)) AS MASTER,
            (SELECT TOP 1 Nation FROM ".DB_GAME.".dbo.cabal_character_table WHERE CharacterIdx = (SELECT TOP 1 CharacterIndex FROM ".DB_GAME.".dbo.GuildMember AS gm2 WHERE gm2.GuildNo = g.GuildNo ORDER BY GroupIndex, RegDate)) AS NATION,
            SUM(CONVERT(BIGINT, c.Reputation)) AS HONOR
        FROM ".DB_GAME.".dbo.Guild g
        JOIN ".DB_GAME.".dbo.GuildMember gm ON g.GuildNo = gm.GuildNo
        JOIN ".DB_GAME.".dbo.cabal_character_table c ON c.CharacterIdx = gm.CharacterIndex
        JOIN ".DB_GAME.".dbo.cabal_guild_level_table g2 ON g.GuildNo = g2.GuildNo
        GROUP BY g.GuildName, g.GuildUrl, g.GuildNo, g2.Point, g2.Level
        ORDER BY g2.Point DESC, HONOR DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getTopAlz($db, $limit = 50) {
    $stmt = $db->prepare("
        WITH AccountWealth AS (
            SELECT 
                a.ID AS AccountID,
                c.Name,
                c.Alz AS CharAlz,
                c.PlayTime,
                w.Alz AS WarehouseAlz,
                -- Sum of all characters' Alz + the shared Warehouse Alz
                SUM(c.Alz) OVER(PARTITION BY a.UserNum) + w.Alz AS CombinedWealth,
                -- Rank characters by PlayTime within the account (Highest = 1)
                ROW_NUMBER() OVER(PARTITION BY a.UserNum ORDER BY c.PlayTime DESC) as PlayRank
            FROM ".DB_GAME.".dbo.cabal_character_table c
            JOIN ".DB_GAME.".dbo.cabal_warehouse_table w ON c.CharacterIdx/16 = w.UserNum
            JOIN Account.dbo.cabal_auth_table a ON c.CharacterIdx/16 = a.UserNum
            WHERE c.Nation <> 3
        )
        SELECT TOP $limit 
            Name, 
            AccountID, 
            CombinedWealth AS TotalAlz,
            PlayTime
        FROM AccountWealth
        WHERE PlayRank = 1 -- Only pull the character with the most playtime
        ORDER BY TotalAlz DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}


function getTopWexp($db, $limit = 50) {
    $stmt = $db->prepare("
        SELECT TOP $limit 
        c.Name, 
        w.WarExp
        FROM Server01.dbo.cabal_character_table c
        JOIN Server01.dbo.cabal_WarExp_table w ON c.CharacterIdx = w.CharacterIdx
        WHERE c.Nation <> 3
        ORDER BY w.WarExp DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}