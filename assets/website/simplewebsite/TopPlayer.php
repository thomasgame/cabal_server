<?php
session_start();
require_once('php/db.php');

function nationLabel($id) {
    return $id == 1 ? 'Capella' : ($id == 2 ? 'Procyon' : 'Neutral');
}

function statusLabel($status) {
    return $status == 1 ? 'Online' : 'Offline';
}

function safe($val) {
    return htmlspecialchars($val);
}

$onlineCount = $conn->query("SELECT COUNT(*) as count FROM Server01.dbo.cabal_character_table WHERE Login = 1")
    ->fetch(PDO::FETCH_ASSOC)['count'];

$topReputation = $conn->query("SELECT TOP 100 Name, LEV, Reputation, Nation, Login FROM Server01.dbo.cabal_character_table WHERE CharacterIdx > 19200 ORDER BY Reputation DESC")
    ->fetchAll(PDO::FETCH_ASSOC);

$topAlz = $conn->query("SELECT TOP 100 Name, LEV, Alz, Nation, Login FROM Server01.dbo.cabal_character_table WHERE CharacterIdx > 19200 ORDER BY Alz DESC")
    ->fetchAll(PDO::FETCH_ASSOC);

$onlinePlayers = $conn->query("SELECT Name, LEV, Nation FROM Server01.dbo.cabal_character_table WHERE Login = 1 and CharacterIdx > 19200 ORDER BY LEV DESC")
    ->fetchAll(PDO::FETCH_ASSOC);

$topPlaytime = $conn->query("SELECT TOP 100 Name, PlayTime, LEV, Nation, Login FROM Server01.dbo.cabal_character_table WHERE CharacterIdx > 19200 ORDER BY PlayTime DESC")
    ->fetchAll(PDO::FETCH_ASSOC);

$playersPerTab = 10;
$totalTabs = ceil((count($onlinePlayers) - 3) / $playersPerTab);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Global Cabal Rankings</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #0f172a; color: #f1f5f9; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
  </style>
  <script>
    function openTab(index) {
      document.querySelectorAll('.tab-content').forEach((tab, i) =>
        tab.classList.toggle('active', i === index)
      );
      document.querySelectorAll('.tab-btn').forEach((btn, i) =>
        btn.classList.toggle('bg-emerald-600', i === index)
      );
    }
    document.addEventListener('DOMContentLoaded', () => openTab(0));
  </script>
</head>
<body class="min-h-screen">

<!-- Navigation -->
<header class="bg-gray-900 sticky top-0 z-50 shadow-lg">
  <div class="max-w-6xl mx-auto px-4 py-3 flex justify-between items-center">
    <h1 class="text-xl font-bold text-white">Global Cabal</h1>
    <nav class="hidden md:flex gap-4 text-sm text-white">
      <a href="index.php" class="hover:text-yellow-400">Home</a>
      <a href="/php/dashboard.php" class="hover:text-yellow-400">Vote</a>
      <a href="login.php" class="hover:text-yellow-400">Login</a>
    </nav>
  </div>
</header>

<!-- Tabs -->
<div class="flex overflow-x-auto bg-gray-800">
  <button onclick="openTab(0)" class="tab-btn flex-1 px-4 py-3 hover:bg-emerald-600 transition-all text-sm">Online Players</button>
  <button onclick="openTab(1)" class="tab-btn flex-1 px-4 py-3 hover:bg-emerald-600 transition-all text-sm">Top 100 Reputation</button>
  <button onclick="openTab(2)" class="tab-btn flex-1 px-4 py-3 hover:bg-emerald-600 transition-all text-sm">Top 100 Alz</button>
  <button onclick="openTab(3)" class="tab-btn flex-1 px-4 py-3 hover:bg-emerald-600 transition-all text-sm">Top 10 Playtime</button>
</div>

<!-- Content Wrapper -->
<div class="max-w-6xl mx-auto p-4 space-y-8">

  <!-- Online Players -->
  <div class="tab-content">
    <h2 class="text-xl font-semibold mb-4">Online Players (<?= count($onlinePlayers) ?>)</h2>
    <div class="grid md:grid-cols-3 gap-6">
      <?php for ($i = 0; $i < min(3, count($onlinePlayers)); $i++): $p = $onlinePlayers[$i]; ?>
      <div class="bg-gray-700 rounded-2xl p-4 text-center shadow-md hover:scale-105 transition">
        <img src="https://via.placeholder.com/80?text=<?= $i+1 ?>" class="mx-auto rounded-full border-4 border-emerald-400 mb-3">
        <h3 class="font-bold text-lg"><?= safe($p['Name']) ?></h3>
        <p>Level: <?= $p['LEV'] ?></p>
        <p><?= nationLabel($p['Nation']) ?></p>
      </div>
      <?php endfor; ?>
    </div>
    <div class="overflow-x-auto mt-6">
      <table class="min-w-full bg-gray-800 rounded-lg overflow-hidden">
        <thead class="bg-gray-700">
          <tr><th class="p-3">#</th><th>Name</th><th>Level</th><th>Nation</th></tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($onlinePlayers, 3) as $i => $p): ?>
          <tr class="text-center border-t border-gray-700">
            <td class="p-2"><?= $i + 4 ?></td>
            <td><?= safe($p['Name']) ?></td>
            <td><?= $p['LEV'] ?></td>
            <td><?= nationLabel($p['Nation']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top Reputation -->
  <div class="tab-content">
    <h2 class="text-xl font-semibold mb-4">Top 100 by Reputation</h2>
    <div class="grid md:grid-cols-3 gap-6">
      <?php for ($i = 0; $i < 3; $i++): $p = $topReputation[$i]; ?>
      <div class="bg-gray-700 rounded-2xl p-4 text-center shadow-md hover:scale-105 transition">
        <img src="https://via.placeholder.com/80?text=<?= $i+1 ?>" class="mx-auto rounded-full border-4 border-emerald-400 mb-3">
        <h3 class="font-bold text-lg"><?= safe($p['Name']) ?></h3>
        <p>Level: <?= $p['LEV'] ?></p>
        <p>Reputation: <?= number_format($p['Reputation']) ?></p>
        <p><?= nationLabel($p['Nation']) ?> - <?= statusLabel($p['Login']) ?></p>
      </div>
      <?php endfor; ?>
    </div>
    <div class="overflow-x-auto mt-6">
      <table class="min-w-full bg-gray-800 rounded-lg overflow-hidden">
        <thead class="bg-gray-700">
          <tr><th class="p-3">#</th><th>Name</th><th>Level</th><th>Reputation</th><th>Nation</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php for ($i = 3; $i < count($topReputation); $i++): $p = $topReputation[$i]; ?>
          <tr class="text-center border-t border-gray-700">
            <td class="p-2"><?= $i+1 ?></td>
            <td><?= safe($p['Name']) ?></td>
            <td><?= $p['LEV'] ?></td>
            <td><?= number_format($p['Reputation']) ?></td>
            <td><?= nationLabel($p['Nation']) ?></td>
            <td><?= statusLabel($p['Login']) ?></td>
          </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top Alz -->
  <div class="tab-content">
    <h2 class="text-xl font-semibold mb-4">Top 100 by Alz</h2>
    <div class="grid md:grid-cols-3 gap-6">
      <?php for ($i = 0; $i < 3; $i++): $p = $topAlz[$i]; ?>
      <div class="bg-gray-700 rounded-2xl p-4 text-center shadow-md hover:scale-105 transition">
        <img src="https://via.placeholder.com/80?text=<?= $i+1 ?>" class="mx-auto rounded-full border-4 border-emerald-400 mb-3">
        <h3 class="font-bold text-lg"><?= safe($p['Name']) ?></h3>
        <p>Level: <?= $p['LEV'] ?></p>
        <p>Alz: <?= number_format($p['Alz']) ?></p>
        <p><?= nationLabel($p['Nation']) ?> - <?= statusLabel($p['Login']) ?></p>
      </div>
      <?php endfor; ?>
    </div>
    <div class="overflow-x-auto mt-6">
      <table class="min-w-full bg-gray-800 rounded-lg overflow-hidden">
        <thead class="bg-gray-700">
          <tr><th class="p-3">#</th><th>Name</th><th>Level</th><th>Alz</th><th>Nation</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php for ($i = 3; $i < count($topAlz); $i++): $p = $topAlz[$i]; ?>
          <tr class="text-center border-t border-gray-700">
            <td class="p-2"><?= $i+1 ?></td>
            <td><?= safe($p['Name']) ?></td>
            <td><?= $p['LEV'] ?></td>
            <td><?= number_format($p['Alz']) ?></td>
            <td><?= nationLabel($p['Nation']) ?></td>
            <td><?= statusLabel($p['Login']) ?></td>
          </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top Playtime -->
  <div class="tab-content">
    <h2 class="text-xl font-semibold mb-4">Top 10 by Playtime</h2>
    <div class="grid md:grid-cols-3 gap-6">
      <?php for ($i = 0; $i < 3; $i++): $p = $topPlaytime[$i]; ?>
      <div class="bg-gray-700 rounded-2xl p-4 text-center shadow-md hover:scale-105 transition">
        <img src="https://via.placeholder.com/80?text=<?= $i+1 ?>" class="mx-auto rounded-full border-4 border-emerald-400 mb-3">
        <h3 class="font-bold text-lg"><?= safe($p['Name']) ?></h3>
        <p>Level: <?= $p['LEV'] ?></p>
        <p>Playtime: <?= number_format($p['PlayTime']) ?></p>
        <p><?= nationLabel($p['Nation']) ?> - <?= statusLabel($p['Login']) ?></p>
      </div>
      <?php endfor; ?>
    </div>
    <div class="overflow-x-auto mt-6">
      <table class="min-w-full bg-gray-800 rounded-lg overflow-hidden">
        <thead class="bg-gray-700">
          <tr><th class="p-3">#</th><th>Name</th><th>Level</th><th>Playtime</th><th>Nation</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php for ($i = 3; $i < count($topPlaytime); $i++): $p = $topPlaytime[$i]; ?>
          <tr class="text-center border-t border-gray-700">
            <td class="p-2"><?= $i+1 ?></td>
            <td><?= safe($p['Name']) ?></td>
            <td><?= $p['LEV'] ?></td>
            <td><?= number_format($p['PlayTime']) ?></td>
            <td><?= nationLabel($p['Nation']) ?></td>
            <td><?= statusLabel($p['Login']) ?></td>
          </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

</body>
</html>
