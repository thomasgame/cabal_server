<?php
session_start();
require_once 'db.php'; // PDO connection to both account DB and forcegem DB

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user'];

// Include the generate_adi.php script to get a dynamic AID
include('generate_aid.php'); // Include the function definition
$usernum = $_SESSION['usernum']; // Get the usernum from session

// Ensure usernum is properly set and valid
if (!isset($usernum) || $usernum <= 0) {
    die("? Invalid session: usernum is missing or invalid.");
}

// Generate AID by passing the usernum
$aid = generateAid($usernum); // Pass usernum as an argument to the function

// Default values
$balance = 0;
$gems = 0;
$forcegemHave = 0; // Initialize forcegem variable

try {
    // Fetch the cash balance
    $stmt = $connCash->prepare("EXEC up_GetUserCashInfo :usernum");
    $stmt->bindParam(':usernum', $usernum, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $balance = $result['CashBonus'];
    } else {
        echo "<p class='text-red-400'>No data returned from stored procedure for cash info.</p>";
    }

    // Fetch forcegem values using the forcegem stored procedure
    $stmt = $connServer->prepare("EXEC dbo.cabal_tool_forcegem_get :usernum");
    $stmt->bindParam(':usernum', $usernum, PDO::PARAM_INT);
    $stmt->execute();

    // Check if data is returned from the forcegem procedure
    $forcegemResult = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if forcegem data was returned
    if ($forcegemResult) {
        $forcegemHave = $forcegemResult['ForcegemHave'];  // Store ForcegemHave value
    } else {
        echo "<p class='text-red-400'>No data returned from stored procedure for forcegem info.</p>";
    }
} catch (PDOException $e) {
    echo "<p class='text-red-400'>Error fetching user data: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <title>Profile - Global Cabal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      background: linear-gradient(to bottom, #000000, #0b0b2b);
      font-family: 'Inter', sans-serif;
    }
  </style>
</head>

<body class="text-white overflow-x-hidden">

<!-- Settings Sidebar -->
<div id="settingsSidebar" class="fixed top-0 right-0 h-full w-64 bg-gray-900 text-white transform translate-x-full transition-transform duration-300 z-40 p-6">
  <h2 class="text-2xl font-bold mb-6">Settings</h2>
  <ul class="space-y-4">
    <li><a href="profile.php" class="hover:text-yellow-400">Edit Profile</a></li>
    <li><a href="change-password.php" class="hover:text-yellow-400">Change Password</a></li>
    <li><a href="manage-security.php" class="hover:text-yellow-400">Manage Security</a></li>
    <li><a href="delete-account.php" class="hover:text-red-400">Delete Account</a></li>
  </ul>
  <button onclick="toggleSettings()" class="absolute top-4 right-4 text-gray-400 hover:text-white text-2xl">&times;</button>
</div>

<!-- Navbar -->
<header class="fixed top-0 left-0 w-full bg-black bg-opacity-80 backdrop-blur-sm z-50">
  <div class="max-w-7xl mx-auto flex justify-between items-center p-4">
    <div class="flex items-center space-x-4">
      <img src="/assets/badge-bronze.png" alt="Badge" class="w-10 h-10">
      <div>
        <div class="text-lg font-bold"><?php echo htmlspecialchars($username); ?></div>
        <div class="text-xs text-gray-400">AID : <?php echo htmlspecialchars($aid); ?></div> <!-- Dynamic AID here -->
      </div>
    </div>

    <div class="flex items-center space-x-4">
      <div class="flex items-center space-x-1">
        <span class="text-yellow-400 font-semibold">Cash:</span>
        <span><?php echo $balance; ?></span>
      </div>
      <div class="flex items-center space-x-1">
        <span class="text-cyan-400 font-semibold">Forcegem:</span>
        <span><?php echo $forcegemHave; ?></span> <!-- Displaying Forcegem value here -->
      </div>
    </div>
    <nav class="hidden md:flex space-x-4">
      <a href="dashboard.php" class="hover:text-yellow-400">Home</a>
      <a href="shop.php" class="hover:text-yellow-400">Shop</a>
      <a href="topup.php" class="hover:text-yellow-400">Top-Up</a>
      <a href="reward.php" class="hover:text-yellow-400">Reward</a>
      <button onclick="toggleSettings()" class="hover:text-yellow-400">Setting</button>
      <a href="/logout.php" class="hover:text-red-400">Logout</a>
    </nav>
    <div class="md:hidden">
      <button id="menu-btn" aria-label="Toggle Menu" class="focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </div>
  </div>
  <div id="menu" class="hidden md:hidden bg-black bg-opacity-80">
    <nav class="flex flex-col items-center py-4 space-y-4">
      <a href="dashboard.php" class="hover:text-yellow-400">Home</a>
      <a href="shop.php" class="hover:text-yellow-400">Shop</a>
      <a href="topup.php" class="hover:text-yellow-400">Top-Up</a>
      <a href="reward.php" class="hover:text-yellow-400">Reward</a>
      <a href="profile.php" class="hover:text-yellow-400 font-bold">Profile</a>
      <button onclick="toggleSettings()" class="hover:text-yellow-400">Setting</button>
      <a href="/logout.php" class="hover:text-red-400">Logout</a>
    </nav>
  </div>
</header>

<main class="pt-32 max-w-5xl mx-auto px-4">
  <!-- Profile Card -->
  <section class="bg-gradient-to-br from-blue-900 to-blue-700 p-8 rounded-xl shadow-lg text-center">
    <img src="/assets/badge-bronze.png" alt="Badge" class="mx-auto w-20 mb-4">
    <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($username); ?></h1>
    <p class="text-sm text-gray-300 mb-4">AID : <?php echo htmlspecialchars($aid); ?></p>

    <a href="update_profile.php" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg">Profile Setting</a>

    <div class="mt-6">
      <p class="text-gray-300">Next Rank 0/0</p>
      <div class="w-full h-3 bg-gray-600 rounded-full mt-2">
        <div class="h-3 bg-yellow-400 rounded-full" style="width:0%"></div>
      </div>
    </div>
  </section>

  <!-- Invite Code Section -->
  <section class="mt-8 bg-gradient-to-br from-gray-800 to-gray-700 p-6 rounded-lg shadow-lg">
    <h2 class="text-xl font-bold mb-4">Invite Code</h2>
    <p class="text-sm mb-4">Invite your friends to unlock rewards!</p>

    <form class="flex flex-col md:flex-row gap-4">
      <input type="text" placeholder="Fill the invite friends" class="flex-grow bg-gray-800 p-2 rounded-lg border border-gray-600 focus:outline-none">
      <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 text-black px-6 py-2 rounded-lg">Submit</button>
    </form>

    <div class="mt-6 flex items-center justify-between">
      <div class="text-green-400 font-bold">Invite Friends 0/20</div>
      <button class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg">+ Invite Friends</button>
    </div>
  </section>
</main>

<script>
  const menuBtn = document.getElementById('menu-btn');
  const menu = document.getElementById('menu');
  const sidebar = document.getElementById('settingsSidebar');

  menuBtn.addEventListener('click', () => {
    menu.classList.toggle('hidden');
  });

  function toggleSettings() {
    sidebar.classList.toggle('translate-x-full');
  }
</script>