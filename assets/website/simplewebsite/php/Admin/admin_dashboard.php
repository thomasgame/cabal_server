<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'db.php'; // Include the database connection file

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit();
}

$username = $_SESSION['user'];
$usernum = $_SESSION['usernum'];

// Admin check (for security)
$adminUsernames = ['admin', 'aerox009x', 'Kuyatel'];
$isAdmin = in_array(strtolower($username), array_map('strtolower', $adminUsernames));

// Query to count registered accounts from the cabal_auth_table
try {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_accounts FROM cabal_auth_table");
    $stmt->execute();
    $totalAccounts = $stmt->fetch(PDO::FETCH_ASSOC)['total_accounts'];
} catch (PDOException $e) {
    die("Error fetching total accounts: " . $e->getMessage());
}

// Query to count the number of online players (from cabal_character_table where login = 1)
try {
    $stmt = $connServer->prepare("SELECT COUNT(*) AS online_players FROM cabal_character_table WHERE login = 1");
    $stmt->execute();
    $onlinePlayers = $stmt->fetch(PDO::FETCH_ASSOC)['online_players'];
} catch (PDOException $e) {
    die("Error fetching online players: " . $e->getMessage());
}

// Additional information can be added here if needed, like active events, top-up records, etc.

?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Global Cabal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(to bottom, #000000, #0b0b2b);
        }
    </style>
</head>
<body class="text-white font-sans overflow-x-hidden">

<!-- Navbar -->


<!-- Main Content -->
<main class="pt-24 px-4 max-w-5xl mx-auto">
    <section class="bg-gray-900 p-6 rounded-lg shadow-lg text-center">
        <p class="text-gray-400 mb-8">Here is the current server status:</p>

        <div class="grid md:grid-cols-2 gap-6">
            <!-- Total Registered Accounts -->
            <div class="bg-yellow-400 text-black py-6 rounded-lg font-semibold text-xl">
                <h2>Total Registered Accounts</h2>
                <p class="text-4xl font-bold"><?php echo $totalAccounts; ?></p>
            </div>

            <!-- Online Players -->
            <div class="bg-yellow-400 text-black py-6 rounded-lg font-semibold text-xl">
                <h2>Online Players</h2>
                <p class="text-4xl font-bold"><?php echo $onlinePlayers; ?></p>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-6 mt-8">
            <a href="manage_users.php" class="bg-yellow-400 text-black py-4 rounded-lg font-semibold hover:bg-yellow-500 transition">Manage Users</a>
            <a href="send_mail.php" class="bg-yellow-400 text-black py-4 rounded-lg font-semibold hover:bg-yellow-500 transition">Send Mail</a>
            <a href="send_cash.php" class="bg-yellow-400 text-black py-4 rounded-lg font-semibold hover:bg-yellow-500 transition">Send Cash</a>
            <a href="manage_topups.php" class="bg-yellow-400 text-black py-4 rounded-lg font-semibold hover:bg-yellow-500 transition">Top-up Records</a>
            <a href="/shop/admin_shop.php" class="bg-yellow-400 text-black py-4 rounded-lg font-semibold hover:bg-yellow-500 transition">Manage Shop</a>
            <a href="manage_rewards.php" class="bg-yellow-400 text-black py-4 rounded-lg font-semibold hover:bg-yellow-500 transition">Reward Control</a>
            <a href="manage_announcements.php" class="bg-yellow-400 text-black py-4 rounded-lg font-semibold hover:bg-yellow-500 transition">Announcements</a>
            <a href="server_settings.php" class="bg-yellow-400 text-black py-4 rounded-lg font-semibold hover:bg-yellow-500 transition">Server Settings</a>
        </div>
    </section>
</main>

</body>
</html>
