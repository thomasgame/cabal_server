<?php
session_start();
require_once('php/db.php');

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = trim($_POST["id"] ?? '');
    $pwd = $_POST["password"] ?? '';

    try {
        $stmt = $conn->prepare("
            SELECT ID, UserNum, Password
            FROM cabal_auth_table
            WHERE LOWER(ID) = LOWER(:id)
        ");
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $stmtPwd = $conn->prepare("
                SELECT pwdcompare(:pwd, Password) AS pwdMatch
                FROM cabal_auth_table
                WHERE ID = :id
            ");
            $stmtPwd->bindParam(":id", $row['ID']);
            $stmtPwd->bindParam(":pwd", $pwd);
            $stmtPwd->execute();
            $pwdResult = $stmtPwd->fetch(PDO::FETCH_ASSOC);

            if ($pwdResult && $pwdResult['pwdMatch'] == 1) {
                session_regenerate_id(true);
                $_SESSION['user'] = $row['ID'];
                $_SESSION['usernum'] = $row['UserNum'];
                $_SESSION['logged_in'] = true;
                header("Location: /php/dashboard.php");
                exit();
            } else {
                $message = "? Invalid password.";
            }
        } else {
            $message = "? Username not found.";
        }
    } catch (PDOException $e) {
        $message = "? Error connecting to database.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Global Cabal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background:
                linear-gradient(rgba(13, 15, 43, 0.9), rgba(17, 17, 17, 0.9)),
                url('assets/background.jpg') no-repeat center center fixed;
            background-size: cover;
            background-blend-mode: overlay;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center text-white">
    <div class="bg-[#1c2230]/90 p-8 rounded-2xl shadow-2xl w-full max-w-md">
 <div class="text-center mb-6">
            <img src="assets/img/logo.png" alt="Global Cabal" class="mx-auto w-32 mb-4">
        </div>

  
        <div class="flex justify-center mb-8">
            <button disabled class="px-6 py-2 bg-blue-800 rounded-tl-lg rounded-bl-lg font-semibold">
                Log in
            </button>
            <a href="registration/registration.html" class="px-6 py-2 bg-[#2b3140] hover:bg-blue-700 transition rounded-tr-lg rounded-br-lg font-semibold">
                Register
            </a>
        </div>

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
                    id="password" 
                    name="password" 
                    placeholder="Password*" 
                    required 
                    class="w-full px-4 py-3 rounded-md bg-[#2b3140] focus:outline-none focus:ring-2 focus:ring-yellow-400"
                >
                <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                    ???
                </button>
            </div>

            <div class="flex items-center justify-between text-sm text-gray-400">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="remember" class="accent-yellow-400">
                    Remember Me
                </label>
                <a href="forgot_password.php" class="text-yellow-400 hover:underline">Forgot Password?</a>
            </div>

            <button 
                type="submit" 
                class="w-full py-3 bg-yellow-400 hover:bg-yellow-500 rounded-md font-bold text-gray-900 transition"
            >
                LOG IN
            </button>
        </form>

        <?php if (!empty($message)): ?>
            <div class="text-center text-red-500 mt-6 font-semibold">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="text-center text-xs text-gray-500 mt-8">
            © 2025 Global Cabal. All rights reserved.
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
