<?php
/**
 * Logout Logic
 * This section handles the server-side session destruction.
 * We perform this at the top, but we don't use a PHP header redirect
 * so that the user can see the "Logout Successful" UI.
 */
session_start();

// 1. Unset all session variables
$_SESSION = array();

// 2. Kill the session cookie in the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session on the server
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out...</title>
    <!-- Tailwind CSS for modern styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom animation for the progress ring */
        @keyframes progress {
            from { stroke-dashoffset: 100; }
            to { stroke-dashoffset: 0; }
        }
        .progress-ring-circle {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: progress 3s linear forwards;
        }
        
        /* Smooth exit animation */
        .fade-out-down {
            animation: fadeOutDown 0.5s ease-in forwards;
        }

        @keyframes fadeOutDown {
            from { opacity: 1; transform: translateY(0) scale(1); }
            to { opacity: 0; transform: translateY(20px) scale(0.95); }
        }

        body {
            /* Background image integration with fallback color */
            background-color: #1a202c;
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('images/bg1.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
        }

        /* Glassmorphism effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen font-sans overflow-hidden p-4">

    <!-- Logout Card -->
    <div id="logoutCard" class="glass-card p-8 md:p-10 rounded-3xl shadow-2xl max-w-sm w-full text-center transform transition-all duration-500 ease-out opacity-0 translate-y-10">
        
        <!-- Success Icon & Progress Container -->
        <div class="relative w-24 h-24 mx-auto mb-8">
            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="44" stroke="#f1f5f9" stroke-width="6" fill="transparent" />
                <circle class="progress-ring-circle" cx="50" cy="50" r="44" stroke="#10b981" stroke-width="6" fill="transparent" stroke-linecap="round" />
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="bg-green-100 p-3 rounded-full shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
        </div>

        <h1 class="text-3xl font-extrabold text-gray-900 mb-3">See you soon!</h1>
        <p class="text-gray-600 mb-8 leading-relaxed">
            You have been successfully logged out.<br>
            Redirecting in <span id="countdown" class="font-bold text-green-600">3</span> seconds...
        </p>

        <!-- Progress Bar (Secondary) -->
        <div class="w-full bg-gray-200 h-1.5 rounded-full overflow-hidden mb-8">
            <div id="linearProgress" class="bg-green-500 h-full w-0 transition-all duration-100"></div>
        </div>

        <!-- Manual Link -->
        <div class="pt-6 border-t border-gray-100">
            <a href="index.php" class="inline-flex items-center text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors group">
                Return to Login
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>
    </div>

    <script>
        const duration = 3000; // 3 seconds
        const countdownEl = document.getElementById('countdown');
        const linearProgress = document.getElementById('linearProgress');
        const card = document.getElementById('logoutCard');
        
        let timeLeft = 3;
        const start = Date.now();

        // Initial entrance animation
        window.addEventListener('load', () => {
            setTimeout(() => {
                card.classList.remove('opacity-0', 'translate-y-10');
            }, 100);
        });

        // Update countdown text and progress bar
        const timerInterval = setInterval(() => {
            const elapsed = Date.now() - start;
            const progress = Math.min((elapsed / duration) * 100, 100);
            
            linearProgress.style.width = progress + '%';
            
            const newTimeLeft = Math.ceil((duration - elapsed) / 1000);
            if (newTimeLeft !== timeLeft && newTimeLeft >= 0) {
                timeLeft = newTimeLeft;
                countdownEl.textContent = timeLeft;
            }

            if (elapsed >= duration) {
                clearInterval(timerInterval);
                // Trigger exit animation before redirect
                card.classList.add('fade-out-down');
                setTimeout(() => {
                    window.location.href = "index.php";
                }, 400);
            }
        }, 50);
    </script>
	
</body>
</html>