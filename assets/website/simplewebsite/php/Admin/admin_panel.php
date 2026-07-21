<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit();
}

$username = $_SESSION['user'];
$usernum = $_SESSION['usernum'];

$adminUsernames = ['admin', 'aerox009x', 'Kuyatel'];
$isAdmin = in_array(strtolower($username), array_map('strtolower', $adminUsernames));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Global Cabal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#facc15'
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            background: linear-gradient(to bottom, #000000, #0b0b2b);
        }
        iframe {
            width: 100%;
            height: calc(100vh - 3rem);
            border: none;
        }
        .sidebar-toggle {
            display: none;
        }
        @media screen and (max-width: 768px) {
            .sidebar {
                position: absolute;
                top: 0;
                left: -100%;
                width: 250px;
                background-color: #1f2937;
                z-index: 10;
                transition: left 0.3s;
            }
            .sidebar.open {
                left: 0;
            }
            .sidebar-toggle {
                display: block;
            }
        }
    </style>
</head>
<body class="text-white font-sans overflow-hidden">

<div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 border-r border-yellow-400 shadow-lg hidden md:flex flex-col sidebar">
        <div class="p-5 border-b border-yellow-400 flex flex-col items-center">
            <div class="flex items-center gap-2">
                <i data-lucide="shield-check" class="text-yellow-400 w-6 h-6"></i>
                <h2 class="text-lg font-bold text-yellow-400">Admin Panel</h2>
            </div>
            <p class="text-xs text-gray-400 mt-1">Global Cabal</p>
        </div>
        <nav class="mt-6 space-y-1 px-3 text-sm font-medium flex-1">
            <button onclick="loadPage('admin_dashboard.php')" id="admin_dashboard-btn" class="block w-full text-left px-4 py-2 rounded-lg hover:bg-yellow-500 hover:text-black transition">Dashboard</button>
            <button onclick="loadPage('manage_users.php')" id="manage_users-btn" class="block w-full text-left px-4 py-2 rounded-lg hover:bg-yellow-500 hover:text-black transition">Manage Users</button>
            <button onclick="loadPage('send_mail.php')" id="send_mail-btn" class="block w-full text-left px-4 py-2 rounded-lg hover:bg-yellow-500 hover:text-black transition">Send Mail</button>
            <button onclick="loadPage('send_cash.php')" id="send_cash-btn" class="block w-full text-left px-4 py-2 rounded-lg hover:bg-yellow-500 hover:text-black transition">Send Cash</button>
            <button onclick="loadPage('manage_topups.php')" id="manage_topups-btn" class="block w-full text-left px-4 py-2 rounded-lg hover:bg-yellow-500 hover:text-black transition">Top-up Records</button>
            <button onclick="loadPage('/shop/admin/admin_shop.php')" id="admin_shop-btn" class="block w-full text-left px-4 py-2 rounded-lg hover:bg-yellow-500 hover:text-black transition">Manage Shop</button>
            <button onclick="loadPage('manage_rewards.php')" id="manage_rewards-btn" class="block w-full text-left px-4 py-2 rounded-lg hover:bg-yellow-500 hover:text-black transition">Reward Control</button>
            <button onclick="loadPage('manage_announcements.php')" id="manage_announcements-btn" class="block w-full text-left px-4 py-2 rounded-lg hover:bg-yellow-500 hover:text-black transition">Announcements</button>
            <button onclick="loadPage('server_settings.php')" id="server_settings-btn" class="block w-full text-left px-4 py-2 rounded-lg hover:bg-yellow-500 hover:text-black transition">Server Settings</button>
 	<button onclick="loadPage('admin_upload.php')" id="server_settings-btn" class="block w-full text-left px-4 py-2 rounded-lg hover:bg-yellow-500 hover:text-black transition">Upload Client</button>
<button onclick="loadPage('cabal_max.php')" id="server_settings-btn" class="block w-full text-left px-4 py-2 rounded-lg hover:bg-yellow-500 hover:text-black transition">Max Char</button>
<button onclick="loadPage('cabal_max_pet.php')" id="server_settings-btn" class="block w-full text-left px-4 py-2 rounded-lg hover:bg-yellow-500 hover:text-black transition">Max PET</button>
	
        </nav>
        <div class="px-4 py-3 border-t border-yellow-400">
            <a href="../logout.php" class="block text-red-500 hover:text-red-300 transition">Logout</a>
        </div>
    </aside>

    <!-- Sidebar Toggle (Mobile) -->
    <button class="sidebar-toggle md:hidden p-4 text-yellow-400" onclick="toggleSidebar()">
        <i data-lucide="menu" class="w-6 h-6"></i>
    </button>

    <!-- Main Content -->
    <main class="flex-1 p-4 overflow-hidden">
        <div class="flex items-center justify-between mb-2">
            <div class="text-yellow-400 font-semibold text-lg">Welcome, Admin <span class="font-bold"><?php echo htmlspecialchars($username); ?></span></div>
            <a href="../dashboard.php" class="text-sm bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded transition font-medium flex items-center gap-1">
                <i data-lucide="log-out" class="w-4 h-4"></i> Character Dashboard
            </a>
        </div>
        <iframe id="contentFrame" src="admin_dashboard.php"></iframe>
    </main>
</div>

<script>
    function loadPage(page) {
        document.getElementById('contentFrame').src = page;
        document.title = 'Admin Panel - ' + page.replace('.php', '').replace('_', ' ').toUpperCase();

        const buttons = document.querySelectorAll('nav button');
        buttons.forEach(btn => btn.classList.remove('bg-yellow-500', 'text-black'));

        const activeBtn = document.getElementById(page.replace('.php', '-btn'));
        if (activeBtn) {
            activeBtn.classList.add('bg-yellow-500', 'text-black');
        }
    }

    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('open');
    }

    lucide.createIcons();
</script>

</body>
</html>
