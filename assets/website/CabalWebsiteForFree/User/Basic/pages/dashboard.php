<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap');

        :root {
            /* Modern Color Palette */
            --bg-dark: #0a0e27;
            --bg-card: #141b34;
            --bg-card-hover: #1a2342;
            --text-primary: #ffffff;
            --text-secondary: #8b92b8;
            --text-muted: #5a6388;
            --accent-cyan: #00d9ff;
            --accent-pink: #ff2e97;
            --accent-purple: #a855f7;
            --accent-orange: #ff6b35;
            --accent-emerald: #10b981;
            --border-subtle: rgba(139, 146, 184, 0.1);
            --glow-cyan: rgba(0, 217, 255, 0.3);
            --glow-pink: rgba(255, 46, 151, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1534 100%);
            color: var(--text-primary);
            font-family: 'Space Grotesk', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            padding: 40px 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Effect */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(0, 217, 255, 0.03) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.5; }
            50% { transform: scale(1.1) rotate(180deg); opacity: 0.8; }
        }

        .dashboard-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Header with Gradient Border */
        .header-block {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-subtle);
        }

        .header-block::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-cyan), var(--accent-pink), var(--accent-purple));
            animation: shimmer 3s linear infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .header-left h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-cyan) 0%, var(--accent-pink) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .header-subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .user-badge {
            text-align: right;
        }

        .user-label {
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 6px;
        }

        .user-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        /* Card Variants */
        .stat-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 28px;
            border: 1px solid var(--border-subtle);
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(255, 255, 255, 0.02) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(139, 146, 184, 0.3);
            background: var(--bg-card-hover);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 16px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
        }

        /* Icon Backgrounds */
        .icon-cyan {
            background: linear-gradient(135deg, rgba(0, 217, 255, 0.15) 0%, rgba(0, 217, 255, 0.05) 100%);
            color: var(--accent-cyan);
            box-shadow: 0 0 20px var(--glow-cyan);
        }

        .icon-pink {
            background: linear-gradient(135deg, rgba(255, 46, 151, 0.15) 0%, rgba(255, 46, 151, 0.05) 100%);
            color: var(--accent-pink);
            box-shadow: 0 0 20px var(--glow-pink);
        }

        .icon-purple {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.15) 0%, rgba(168, 85, 247, 0.05) 100%);
            color: var(--accent-purple);
        }

        .icon-orange {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.15) 0%, rgba(255, 107, 53, 0.05) 100%);
            color: var(--accent-orange);
        }

        .icon-emerald {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.05) 100%);
            color: var(--accent-emerald);
        }

        /* Status Pills */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            margin-top: 4px;
        }

        .status-active {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.1) 100%);
            color: var(--accent-emerald);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-banned {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(239, 68, 68, 0.1) 100%);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            animation: blink 2s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* Email Card */
        .email-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(20, 27, 52, 0.8) 100%);
            border-radius: 16px;
            padding: 28px;
            border: 1px solid var(--border-subtle);
            position: relative;
            overflow: hidden;
        }

        .email-card::after {
            content: '✉️';
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 4rem;
            opacity: 0.05;
        }

        .email-label {
            color: var(--text-secondary);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .email-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
            position: relative;
            z-index: 1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 20px 16px;
            }

            .header-block {
                padding: 24px;
            }

            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .user-badge {
                text-align: left;
            }

            .header-left h1 {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 480px) {
            .header-left h1 {
                font-size: 1.75rem;
            }

            .stat-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Header -->
        <div class="header-block">
            <div class="header-content">
                <div class="header-left">
                    <h1>Dashboard</h1>
                    <p class="header-subtitle">Welcome back to your command center</p>
                </div>
                <div class="user-badge">
                    <div class="user-label">Logged in as</div>
                    <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <!-- Cash Card -->
            <div class="stat-card">
                <div class="stat-icon icon-cyan">💰</div>
                <div class="stat-label">Total Cash</div>
                <div class="stat-value" style="color: var(--accent-cyan);">
                    <?php echo number_format($ecoin); ?>
                </div>
            </div>

            <!-- Gems Card -->
            <div class="stat-card">
                <div class="stat-icon icon-pink">💎</div>
                <div class="stat-label">Force Gems</div>
                <div class="stat-value" style="color: var(--accent-pink);">
                    <?php echo number_format($forceGem); ?>
                </div>
            </div>

            <!-- Status Card -->
            <div class="stat-card">
                <div class="stat-icon icon-emerald">⚡</div>
                <div class="stat-label">Account Status</div>
                <div>
                    <?php if ($status == 1): ?>
                        <span class="status-pill status-active">
                            <span class="status-dot"></span>
                            ACTIVE
                        </span>
                    <?php else: ?>
                        <span class="status-pill status-banned">
                            <span class="status-dot"></span>
                            BANNED
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Join Date Card -->
            <div class="stat-card">
                <div class="stat-icon icon-purple">📅</div>
                <div class="stat-label">Member Since</div>
                <div class="stat-value" style="color: var(--accent-purple); font-size: 1.4rem;">
                    <?php echo date('M d, Y', strtotime($joinDate)); ?>
                </div>
            </div>

            <!-- Characters Card -->
            <div class="stat-card">
                <div class="stat-icon icon-orange">👥</div>
                <div class="stat-label">Characters Owned</div>
                <div class="stat-value" style="color: var(--accent-orange);">
                    <?php echo number_format($characterCount); ?>
                </div>
            </div>

            <!-- Playtime Card -->
            <div class="stat-card">
                <div class="stat-icon icon-cyan">⏱️</div>
                <div class="stat-label">Total Playtime</div>
                <div class="stat-value" style="color: var(--accent-cyan); font-size: 1.4rem;">
                    <?php echo formatPlayTime($playtime); ?>
                </div>
            </div>
        </div>

        <!-- Email Card -->
        <div class="email-card">
            <div class="email-label">Registered Email Address</div>
            <div class="email-value">
                <?php echo $email ? htmlspecialchars($email) : 'No email associated'; ?>
            </div>
        </div>
    </div>
</body>
</html>