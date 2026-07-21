<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cabal Community | Evolution in Progress</title>
    <!-- Modern Font: Orbitron for headers, Inter for readability -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent: #ff007b;
            --accent-glow: rgba(255, 0, 123, 0.5);
            --bg-dark: #0a0a0c;
            --card-bg: rgba(15, 15, 20, 0.7);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        * {
            box-sizing: border-box;
            user-select: none;
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            background-color: var(--bg-dark);
            /* Fallback gradient with your background image logic */
            background-image: 
                radial-gradient(circle at 50% 50%, rgba(255, 0, 123, 0.05) 0%, transparent 50%),
                linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)), 
                url('../images/maintenance/bg_170926_main.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Inter', sans-serif;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Subtle animated scanline effect */
        body::before {
            content: " ";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, transparent 50%, rgba(0, 0, 0, 0.1) 51%);
            background-size: 100% 4px;
            z-index: 1;
            pointer-events: none;
        }

        .main-wrapper {
            position: relative;
            z-index: 2;
            width: 90%;
            max-width: 700px;
            perspective: 1000px;
        }

        .container {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 32px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.8), 
                        inset 0 0 20px rgba(255, 255, 255, 0.02);
            animation: containerEntry 1.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 0, 123, 0.1);
            border: 1px solid var(--accent);
            padding: 6px 16px;
            border-radius: 100px;
            color: var(--accent);
            font-family: 'Orbitron', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 24px;
            text-transform: uppercase;
            box-shadow: 0 0 15px rgba(255, 0, 123, 0.2);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: var(--accent);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        h1 {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: clamp(2rem, 5vw, 3.5rem);
            margin: 0 0 16px 0;
            letter-spacing: -1px;
            line-height: 1.1;
            background: linear-gradient(180deg, #fff 0%, #a1a1aa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .description {
            color: #94a3b8;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 40px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .launch-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
        }

        .launch-box::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
            animation: shine 4s infinite;
        }

        .launch-label {
            font-size: 0.8rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 12px;
            display: block;
        }

        .launch-date {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            color: #fff;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
            font-weight: 700;
        }

        .actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .btn-discord {
            position: relative;
            padding: 18px 48px;
            background: #fff;
            color: #000;
            text-decoration: none;
            border-radius: 12px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
            display: inline-flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .btn-discord:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 20px 40px rgba(255, 0, 123, 0.3);
            background: var(--accent);
            color: #fff;
        }

        .support-text {
            color: #475569;
            font-size: 0.85rem;
            font-style: italic;
        }

        /* Keyframes */
        @keyframes containerEntry {
            from { opacity: 0; transform: translateY(40px) rotateX(-5deg); }
            to { opacity: 1; transform: translateY(0) rotateX(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.4); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes shine {
            to { left: 100%; }
        }

        /* Mobile specific fixes */
        @media (max-width: 480px) {
            .container { padding: 40px 20px; }
            h1 { font-size: 2.2rem; }
            .launch-date { font-size: 1.2rem; }
        }
    </style>
</head>
<body>

    <div class="main-wrapper">
        <div class="container">
            <div class="status-badge">
                <span class="status-dot"></span>
                System Update
            </div>

            <h1>Server Maintenance</h1>
            
            <p class="description">
                We are currently upgrading the Cabal infrastructure to provide a superior gaming experience. Access will be restored momentarily.
            </p>

            <div class="launch-box">
                <span class="launch-label">Estimated Return</span>
                <div class="launch-date">January 24, 2026 &bull; 15:30 UTC</div>
            </div>

            <div class="actions">
                <a href="https://discord.gg/zH6sQmKq" class="btn-discord">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515a.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0a12.64 12.64 0 0 0-.617-1.25a.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057a19.9 19.9 0 0 0 5.993 3.03a.078.078 0 0 0 .084-.028a14.09 14.09 0 0 0 1.226-1.994a.076.076 0 0 0-.041-.106a13.107 13.107 0 0 1-1.872-.892a.077.077 0 0 1-.008-.128a10.2 10.2 0 0 0 .372-.292a.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.196.373.292a.077.077 0 0 1-.006.127a12.299 12.299 0 0 1-1.873.892a.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028a19.839 19.839 0 0 0 6.002-3.03a.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.956-2.419 2.157-2.419c1.21 0 2.176 1.086 2.157 2.419c0 1.334-.956 2.419-2.157 2.419zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.955-2.419 2.157-2.419c1.21 0 2.176 1.086 2.157 2.419c0 1.334-.946 2.419-2.157 2.419z"/>
                    </svg>
                    Join the Community
                </a>
                <span class="support-text">Thank you for your patience, Warrior.</span>
            </div>
        </div>
    </div>

    <script>
        // Security / Anti-Inspect Logic (Maintained from original)
        document.addEventListener("contextmenu", e => e.preventDefault());
        document.addEventListener("keydown", e => {
            if (
                e.keyCode == 123 || 
                (e.ctrlKey && e.shiftKey && (e.keyCode == 73 || e.keyCode == 74)) || 
                (e.ctrlKey && e.keyCode == 85) ||
                (e.keyCode == 83 && (navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey))
            ) {
                e.preventDefault();
                return false;
            }
        });

        // Optional: Interactive parallax effect
        document.addEventListener('mousemove', (e) => {
            const container = document.querySelector('.container');
            const xAxis = (window.innerWidth / 2 - e.pageX) / 50;
            const yAxis = (window.innerHeight / 2 - e.pageY) / 50;
            container.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
        });
    </script>
</body>
</html>