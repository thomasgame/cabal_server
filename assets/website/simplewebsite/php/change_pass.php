<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load DB credentials from shared file
require_once 'db.php';

$connectionOptions = [
    "Database" => "Account",
    "Uid" => $username,
    "PWD" => $password,
    "CharacterSet" => "UTF-8"
];

$serverName = $serverName;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $old_pass = $_POST['old_pass'] ?? '';
    $new_pass = $_POST['new_pass'] ?? '';

    if (!$id || !$old_pass || !$new_pass) {
        echo json_encode(["status" => "error", "message" => "Missing parameters."]);
        exit;
    }

    $conn = sqlsrv_connect($serverName, $connectionOptions);
    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "DB connection failed: " . print_r(sqlsrv_errors(), true)]);
        exit;
    }

    $result = 0;
    $sql = "{CALL cabal_tool_ChangePassword(?, ?, ?, ?)}";
    $params = [
        [$id, SQLSRV_PARAM_IN],
        [$old_pass, SQLSRV_PARAM_IN],
        [$new_pass, SQLSRV_PARAM_IN],
        [&$result, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT]
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        echo json_encode(["status" => "error", "message" => "Query failed: " . print_r(sqlsrv_errors(), true)]);
        sqlsrv_close($conn);
        exit;
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    switch ($result) {
        case 1:
            session_unset();
            session_destroy();
            echo json_encode(["status" => "success", "message" => "Password changed successfully. Logging out..."]);
            break;
        case -1:
            echo json_encode(["status" => "error", "message" => "Old password is incorrect."]);
            break;
        default:
            echo json_encode(["status" => "error", "message" => "Password change failed."]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

    <meta charset="UTF-8">
    <title>Change Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background:
                linear-gradient(rgba(13, 15, 43, 0.9), rgba(17, 17, 17, 0.9)),
                url('/assets/background.jpg') no-repeat center center fixed;
            background-size: cover;
            background-blend-mode: overlay;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>



<header>
<!-- Navigation Bar -->
<header class="fixed top-0 left-0 w-full bg-gradient-to-r from-black to-gray-900 backdrop-blur-sm shadow-lg z-50">
  <div class="max-w-7xl mx-auto flex justify-between items-center p-4">
    <div class="text-2xl font-extrabold tracking-wider text-yellow-400">Global Cabal</div>
       <nav class="hidden md:flex space-x-6 text-sm font-medium items-center">
      <a href="/php/dashboard.php#profile" class="hover:text-yellow-400">Profile</a>
      <a href="/php/dashboard.php#voting" class="hover:text-yellow-400">Voting</a>
      <a href="/php/dashboard.php#otp" class="hover:text-yellow-400">OTP</a>
      <a href="/php/dashboard.php#characters" class="hover:text-yellow-400">Characters</a>
      <a href="/shop/main_shop.php" class="hover:text-yellow-400">Shop</a>
      <button onclick="toggleSettings()" class="hover:text-yellow-400">Settings</button>
      <a href="/logout.php" class="hover:text-red-400">Logout</a>
    </nav>
    <div class="md:hidden">
      <button id="menu-btn" class="focus:outline-none">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </div>
  </div>
  <!-- Mobile Menu -->
  <div id="menu" class="hidden md:hidden bg-gray-900 px-6 py-4">
    <nav class="flex flex-col space-y-3 text-sm">
      <a href="#profile" class="hover:text-yellow-400">Profile</a>
      <a href="#voting" class="hover:text-yellow-400">Voting</a>
      <a href="#otp" class="hover:text-yellow-400">OTP</a>
      <a href="#characters" class="hover:text-yellow-400">Characters</a>
      <a href="/shop/main_shop.php" class="hover:text-yellow-400">Shop</a>
           <button onclick="toggleSettings()" class="hover:text-yellow-400">Settings</button>
      <a href="/logout.php" class="hover:text-red-400">Logout</a>
    </nav>
  </div>


<!-- Settings Sidebar -->
<aside id="settingsSidebar" class="fixed top-0 right-0 h-full w-64 bg-gray-900 text-white transform translate-x-full transition-transform duration-300 z-40 p-6">
  <h2 class="text-xl font-bold mb-4">Account Settings</h2>
  <ul class="space-y-3 text-sm">
    <li><a href="update_profile.php" class="hover:text-yellow-400">Edit Profile</a></li>
    <li><a href="change_pass.php" class="hover:text-yellow-400">Change Password</a></li>
    <li><a href="manage-security.php" class="hover:text-yellow-400">Manage Security</a></li>
    <li><a href="delete-account.php" class="hover:text-red-400">Delete Account</a></li>
  </ul>
  <button onclick="toggleSettings()" class="absolute top-4 right-4 text-gray-400 hover:text-white text-xl">&times;</button>
</aside>

</header>

<body class="min-h-screen flex items-center justify-center text-white">
    <div class="bg-[#1c2230]/90 p-8 rounded-2xl shadow-2xl w-full md:w-[500px] max-w-xl">
  
        <h2 class="text-center text-2xl text-white mb-8 font-semibold">Change Password</h2>

        <form method="POST" class="space-y-6">
            <input 
                type="text" 
                name="id" 
                placeholder="Username*" 
                required 
                autofocus 
                class="w-full px-4 py-3 rounded-md bg-[#2b3140] focus:outline-none focus:ring-2 focus:ring-yellow-400"
            >
            
            <div class="relative">
                <input 
                    type="password" 
                    name="old_pass" 
                    placeholder="Old Password*" 
                    required 
                    class="w-full px-4 py-3 rounded-md bg-[#2b3140] focus:outline-none focus:ring-2 focus:ring-yellow-400"
                >
            </div>

            <div class="relative">
                <input 
                    type="password" 
                    name="new_pass" 
                    placeholder="New Password*" 
                    required 
                    class="w-full px-4 py-3 rounded-md bg-[#2b3140] focus:outline-none focus:ring-2 focus:ring-yellow-400"
                >
            </div>

            <button 
                type="submit" 
                class="w-full py-3 bg-yellow-400 hover:bg-yellow-500 rounded-md font-bold text-gray-900 transition"
            >
                Change Password
            </button>
        </form>

        <div class="text-center text-xs text-gray-500 mt-8">
            © 2025 Global Cabal. All rights reserved.
        </div>

        <div id="result" class="text-center mt-6"></div>
    </div>
</body>
</html>

<script>
document.getElementById("changePassForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const resultEl = document.getElementById("result");

    resultEl.textContent = "Processing...";
    resultEl.className = "";

    fetch("change_pass.php", {
        method: "POST",
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            const data = JSON.parse(text);
            resultEl.textContent = data.message;
            resultEl.className = data.status === "success" ? "success" : "error";
        } catch (e) {
            console.error("Invalid JSON:", text);
            resultEl.textContent = "Unexpected server response.";
            resultEl.className = "error";
        }
    })
    .catch(err => {
        console.error("Request failed:", err);
        resultEl.textContent = "Request failed. Please try again.";
        resultEl.className = "error";
    });
});
</script>

