<?php
session_start();
require_once 'php/db.php'; // Adjust path as needed

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? '');
    $email = trim($_POST["email"] ?? '');

    if (!$username || !$email) {
        $message = "Please fill in all fields.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT ID FROM cabal_auth_table WHERE ID = :username AND Email = :email");
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $message = "Account verified. Please contact the administrator to reset your password.";
            } else {
                $message = "No matching account found.";
            }
        } catch (Exception $e) {
            $message = "Error: Could not process request.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Global Cabal</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>
<body class="flex items-center justify-center text-white">
    <div class="bg-[#1c2230]/90 p-8 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="text-center mb-6">
            <img src="assets/img/logo.png" alt="Global Cabal" class="mx-auto w-32 mb-4">
        </div>

        <h2 class="text-center text-2xl mb-6 font-semibold">Forgot Password</h2>

        <form method="POST" class="space-y-6">
            <input 
                type="text" 
                name="username" 
                placeholder="Username*" 
                required 
                class="w-full px-4 py-3 rounded-md bg-[#2b3140] focus:outline-none focus:ring-2 focus:ring-yellow-400"
            >
            <input 
                type="email" 
                name="email" 
                placeholder="Email Address*" 
                required 
                class="w-full px-4 py-3 rounded-md bg-[#2b3140] focus:outline-none focus:ring-2 focus:ring-yellow-400"
            >

            <button 
                type="submit" 
                class="w-full py-3 bg-yellow-400 hover:bg-yellow-500 rounded-md font-bold text-gray-900 transition"
            >
                Verify Account
            </button>
        </form>

        <?php if (!empty($message)): ?>
            <div class="text-center mt-6 text-sm text-yellow-300">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="text-center text-xs text-gray-500 mt-8">
            © 2025 Global Cabal. All rights reserved.
        </div>
    </div>
</body>
</html>
