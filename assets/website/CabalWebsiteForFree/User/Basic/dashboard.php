<?php
require_once('../../_connect/database.php');
require_once('../../_connect/settings.php');
require_once('../../auth_check.php');

$db = new Database();
$conn = $db->getConnection();
$username = $_SESSION['username'];

// Unified Data Fetching
$UserNum = $db->getUserNum($username);
$ecoin = $db->getCash($UserNum);
$forceGem = $db->getForceGem($UserNum);
$status = $db->getStatus($username);
$joinDate = $db->getJoinDate($username);
$characterCount = $db->getCharacterCount($UserNum);
$email = $db->getEmail($username);
$playtime = $db->getPlaytime($username);
$isOnline = ($status == 1);
$isAdmin = $db->isAdmin($username);

$currentPage = $_GET['page'] ?? 'dashboard';
$message = "";
$msgType = "";

// Helper Functions
function formatPlayTime($minutes) {
    $days = floor($minutes / 1440);
    $hours = floor(($minutes % 1440) / 60);
    $mins = $minutes % 60;
    return ($days > 0 ? $days . 'd ' : '') . ($hours > 0 ? $hours . 'h ' : '') . $mins . 'm';
}

function getNationName($id) {
    return [1 => "Capella", 2 => "Procyon", 3 => "Game Master"][$id] ?? "No Nation";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabal Online | User Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-deep: #020408;
            --sidebar-bg: rgba(8, 10, 15, 0.95);
            --card-glass: rgba(17, 25, 40, 0.75);
            --accent-primary: #00f2ff;
            --accent-secondary: #7000ff;
            --accent-glow: rgba(0, 242, 255, 0.4);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --sidebar-width: 280px;
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-deep);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 0% 0%, rgba(112, 0, 255, 0.1) 0%, transparent 35%),
                radial-gradient(circle at 100% 100%, rgba(0, 242, 255, 0.08) 0%, transparent 35%);
        }
        
        img { pointer-events: none; -webkit-user-drag: none; }
        * { box-sizing: border-box; margin: 0; padding: 0; }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent-secondary); }

        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            backdrop-filter: blur(25px);
            border-right: 1px solid var(--border-color);
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: var(--transition-smooth);
        }

        .sidebar-header { padding: 32px 24px; display: flex; align-items: center; gap: 16px; }

        .logo-box {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 25px rgba(0, 242, 255, 0.25);
            flex-shrink: 0;
        }

        .brand-name {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700; font-size: 1.4rem; letter-spacing: -0.5px;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        .nav-list { padding: 0 16px; flex: 1; overflow-y: auto; }
        .category-group { margin-bottom: 24px; }

        .category-label {
            padding: 12px; font-size: 0.7rem; font-weight: 800;
            color: var(--text-dim); text-transform: uppercase; letter-spacing: 1.5px;
            display: flex; align-items: center; justify-content: space-between;
            cursor: pointer; opacity: 0.8; transition: var(--transition-smooth);
        }

        .category-label:hover { opacity: 1; color: #fff; }
        .category-content { transition: max-height 0.4s ease, opacity 0.3s ease; max-height: 1000px; overflow: hidden; }
        .category-content.hidden { max-height: 0; opacity: 0; pointer-events: none; }

        .nav-link {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px;
            color: var(--text-dim); text-decoration: none; border-radius: 12px;
            font-size: 0.9rem; font-weight: 500; transition: var(--transition-smooth);
            margin-bottom: 2px;
        }

        .nav-link i { width: 20px; height: 20px; transition: inherit; }
        .nav-link:hover { color: #fff; background: rgba(255, 255, 255, 0.05); transform: translateX(4px); }
        .nav-link.active { background: rgba(0, 242, 255, 0.1); color: var(--accent-primary); box-shadow: inset 0 0 0 1px rgba(0, 242, 255, 0.2); }
        
        .nav-link.admin-only { color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.1); margin-top: 4px; }
        .nav-link.admin-only:hover { background: rgba(251, 191, 36, 0.1); }

        .main-content { margin-left: var(--sidebar-width); flex: 1; min-height: 100vh; padding: 40px; }

        /* Security Elements */
        #securityOverlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.95);
            display: none; align-items: center; justify-content: center; z-index: 99999;
            backdrop-filter: blur(20px);
        }

        #securityModal {
            background: #0f172a; padding: 40px; border-radius: 24px; max-width: 420px;
            text-align: center; border: 2px solid #ef4444; box-shadow: 0 0 80px rgba(239, 68, 68, 0.3);
        }

        .warning-glow {
            width: 80px; height: 80px; background: rgba(239, 68, 68, 0.15);
            border-radius: 20px; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px; color: #ef4444; animation: pulse-red 1.5s infinite;
        }

        @keyframes pulse-red {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); }
        }

        .close-btn {
            margin-top: 24px; width: 100%; padding: 16px; background: #ef4444;
            border: none; border-radius: 12px; color: white; font-weight: 800;
            cursor: pointer; transition: var(--transition-smooth);
        }

        .sidebar-footer { padding: 20px; border-top: 1px solid var(--border-color); }

        .logout-card {
            background: rgba(239, 68, 68, 0.08); padding: 14px; border-radius: 12px;
            color: #f87171; display: flex; align-items: center; justify-content: center;
            gap: 10px; text-decoration: none; font-weight: 600; transition: var(--transition-smooth);
        }

        .logout-card:hover { background: #ef4444; color: white; }

        @media (max-width: 1024px) {
            :root { --sidebar-width: 80px; }
            .brand-name, .nav-link span, .category-label span, .chevron-icon { display: none; }
            .sidebar-header { justify-content: center; }
            .nav-link { justify-content: center; }
        }
    </style>
</head>
<body>

    <div id="securityOverlay">
        <div id="securityModal">
            <div class="warning-glow"><i data-lucide="shield-alert" size="40"></i></div>
            <h2 style="font-family: 'Space Grotesk'; margin-bottom: 12px; color: #fff;">SECURITY WARNING</h2>
            <p id="securityMessage" style="color: var(--text-dim); font-size: 0.95rem;">Action restricted for security reasons.</p>
            <button class="close-btn" onclick="location.reload();">Reload Page</button>
        </div>
    </div>

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-box"><i data-lucide="zap" size="20" color="#fff" fill="#fff"></i></div>
            <span class="brand-name">Dashboard</span>
        </div>

        <nav class="nav-list">
            <div class="category-group">
                <div class="category-label"><span>General</span></div>
                <a href="?page=dashboard" class="nav-link <?= $currentPage == 'dashboard' ? 'active' : '' ?>">
                    <i data-lucide="grid"></i><span>Overview</span>
                </a>
                <a href="?page=characters" class="nav-link <?= $currentPage == 'characters' ? 'active' : '' ?>">
                    <i data-lucide="users"></i><span>Characters</span>
                </a>
                <a href="?page=settings" class="nav-link <?= $currentPage == 'settings' ? 'active' : '' ?>">
                    <i data-lucide="shield-check"></i><span>Security</span>
                </a>
            </div>

            <div class="category-group">
                <div class="category-label" onclick="toggleCategory('vaultContent', 'vaultChevron')">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: #09E1E3" data-lucide="vault"></span>
                        <span style="color: #09E1E3">Vault</span>
                    </div>
                    <i data-lucide="chevron-down" size="14" class="chevron-icon" id="vaultChevron"></i>
                </div>
                <div id="vaultContent" class="category-content">
                    <a href="?page=dailylogin" class="nav-link <?= $currentPage == 'dailylogin' ? 'active' : '' ?>"><i data-lucide="calendar-days"></i><span>Daily Check-in</span></a>
                </div>
            </div>

            <div class="category-group">
                <div class="category-label" onclick="toggleCategory('arcadeContent', 'arcadeChevron')">
                     <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: #09E1E3" data-lucide="gamepad-2"></span>
                        <span style="color: #09E1E3">Arcade</span>
                    </div>
                    <i data-lucide="chevron-down" size="14" class="chevron-icon" id="arcadeChevron"></i>
                </div>
                <div id="arcadeContent" class="category-content">
                    <a href="?page=limbo" class="nav-link <?= $currentPage == 'limbo' ? 'active' : '' ?>"><i data-lucide="trending-up"></i><span>Limbo</span></a>
                    <a href="?page=plinko" class="nav-link <?= $currentPage == 'plinko' ? 'active' : '' ?>"><i data-lucide="layers-3"></i><span>Plinko</span></a>
                </div>
            </div>

            <?php if ($isAdmin): ?>
            <div class="category-group">
                <div class="category-label" onclick="toggleCategory('adminContent', 'adminChevron')">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: #C21F00" data-lucide="shield-check"></span>
                        <span style="color: #C21F00">Admin Panel</span>
                    </div>
                    <i data-lucide="chevron-down" size="14" class="chevron-icon" id="adminChevron"></i>
                </div>
                <div id="adminContent" class="category-content">
                    <a href="?page=admin_panel" class="nav-link admin-only <?= $currentPage == 'admin_panel' ? 'active' : '' ?>"><i data-lucide="cpu"></i><span>Core Logic</span></a>
                    <a href="?page=admin_management" class="nav-link admin-only <?= $currentPage == 'admin_management' ? 'active' : '' ?>"><i data-lucide="box"></i><span>Reward Gen</span></a>
                    <a href="?page=admin_insight" class="nav-link admin-only <?= $currentPage == 'admin_insight' ? 'active' : '' ?>"><i data-lucide="monitor"></i><span>Insights</span></a>
                    <a href="?page=admin_settings" class="nav-link admin-only <?= $currentPage == 'admin_settings' ? 'active' : '' ?>"><i data-lucide="settings"></i><span>System Setting</span></a>
                    <a href="?page=admin_max_system" class="nav-link admin-only <?= $currentPage == 'admin_max_system' ? 'active' : '' ?>"><i data-lucide="maximize"></i><span>Max System</span></a>
                </div>
            </div>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="../../logout.php" class="logout-card"><i data-lucide="power" size="16"></i><span>END SESSION</span></a>
        </div>
    </aside>

    <main class="main-content">
        <?php 
        $pageFile = "pages/" . $currentPage . ".php";
        
        // Check for Admin access (important security)
        if (strpos($currentPage, 'admin_') !== false && !$isAdmin) {
            echo "<div style='display:flex; flex-direction:column; align-items:center; justify-content:center; height:60vh;'>
                    <div style='background:rgba(239,68,68,0.1); padding:40px; border-radius:30px; border:1px solid rgba(239,68,68,0.2); text-align:center;'>
                        <i data-lucide='lock' size='64' style='color:#ef4444; margin-bottom:20px;'></i>
                        <h1 style='font-family:Space Grotesk; margin-bottom:10px;'>PROTOCOL BREACH</h1>
                        <p style='color:var(--text-dim)'>Access denied. Security clearance insufficient.</p>
                    </div>
                  </div>";
        } elseif (file_exists($pageFile)) {
            include($pageFile);
        } else {
            include("pages/dashboard.php"); 
        }
        ?>
    </main>

    <script>
        lucide.createIcons();

        const triggerSecurity = (reason = "Inspection") => {
            const overlay = document.getElementById('securityOverlay');
            const msg = document.getElementById('securityMessage');
            if (reason === "RightClick") msg.innerText = "Right-clicking is disabled for security reasons.";
            else msg.innerText = "Access to Developer Tools is restricted.";
            if (overlay.style.display !== 'flex') overlay.style.display = 'flex';
        };

        function toggleCategory(contentId, chevronId) {
            const content = document.getElementById(contentId);
            const chevron = document.getElementById(chevronId);
            if(!content) return;
            const isHidden = content.classList.toggle('hidden');
            if (chevron) chevron.style.transform = isHidden ? 'rotate(-90deg)' : 'rotate(0deg)';
            localStorage.setItem('sidebar_' + contentId, !isHidden);
        }

        document.addEventListener('DOMContentLoaded', () => {
            ['vaultContent', 'arcadeContent', 'adminContent'].forEach(id => {
                const content = document.getElementById(id);
                const chevron = document.getElementById(id.replace('Content', 'Chevron'));
                if (!content) return;
                const wasExpanded = localStorage.getItem('sidebar_' + id) === 'true';
                const hasActive = content.querySelector('.active');
                if (wasExpanded || hasActive) {
                    content.classList.remove('hidden');
                    if(chevron) chevron.style.transform = 'rotate(0deg)';
                } else {
                    content.classList.add('hidden');
                    if(chevron) chevron.style.transform = 'rotate(-90deg)';
                }
            });
        });

        // Optional: Keep these for UI protection, or remove if you want full freedom
        document.addEventListener('contextmenu', e => { e.preventDefault(); triggerSecurity("RightClick"); });
        document.onkeydown = function(e) {
            if (e.keyCode == 123 || (e.ctrlKey && e.shiftKey && (e.keyCode == 73 || e.keyCode == 74 || e.keyCode == 67)) || (e.ctrlKey && (e.keyCode == 85 || e.keyCode == 83))) {
                triggerSecurity();
                return false;
            }
        };
    </script>
</body>
</html>