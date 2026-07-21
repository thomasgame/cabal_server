<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || strtolower($_SESSION['user']) !== strtolower($adminUsername)) {
    header("Location: dashboard.php");
    exit();
}

$mailMessage = $_SESSION['mailMessage'] ?? "";
$cashMessage = $_SESSION['cashMessage'] ?? "";
unset($_SESSION['mailMessage'], $_SESSION['cashMessage']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['formType'] === 'mail') {
        try {
            $stmt = $connServer->prepare("EXEC dbo.cabal_sp_mail_send_GM 
                @ReceiverCharIdx = :charIdx, 
                @Title = :title, 
                @Content = :content, 
                @ItemKindIdx = :itemKindIdx, 
                @ItemOption = :itemOption, 
                @ItemDurationIdx = :itemDurationIdx, 
                @ExpirationDay = :expirationDay");

            $stmt->execute([
                ':charIdx' => intval($_POST['charIdx']),
                ':title' => $_POST['title'],
                ':content' => $_POST['content'],
                ':itemKindIdx' => intval($_POST['itemKindIdx']),
                ':itemOption' => intval($_POST['itemOption']),
                ':itemDurationIdx' => intval($_POST['itemDurationIdx']),
                ':expirationDay' => intval($_POST['expirationDay']),
            ]);
            $_SESSION['mailMessage'] = "Mail sent successfully!";
        } catch (PDOException $e) {
            $_SESSION['mailMessage'] = "Error: " . $e->getMessage();
        }
        header("Location: admin_panel.php");
        exit();
    }

    if ($_POST['formType'] === 'cash') {
        try {
            $stmt = $connCash->prepare("EXEC dbo.up_AddMyCashItemByItem 
                @UserNum = :userNum, 
                @TranNo = 0, 
                @ServerIdx = 0, 
                @ItemIdx = :itemIdx, 
                @ItemOpt = :itemOpt, 
                @DurationIdx = :durationIdx, 
                @Memo = :memo");

            $stmt->execute([
                ':userNum' => intval($_POST['userNum']),
                ':itemIdx' => intval($_POST['itemIdx']),
                ':itemOpt' => intval($_POST['itemOpt']),
                ':durationIdx' => intval($_POST['durationIdx']),
                ':memo' => $_POST['memo'] ?? 'publisher'
            ]);
            $_SESSION['cashMessage'] = "Cash item sent successfully!";
        } catch (PDOException $e) {
            $_SESSION['cashMessage'] = "Error: " . $e->getMessage();
        }
        header("Location: admin_panel.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Send Tools</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(to bottom, #111827, #000000); }

        #resultsMail li:hover,
        #resultsCash li:hover,
        #userResults li:hover {
            background-color: #facc15;
            color: black;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="text-white font-sans min-h-screen flex flex-col">

    <!-- Top Navigation Bar -->
    <nav class="bg-black text-white flex justify-between items-center px-8 py-4 border-b border-gray-900">
        <div class="text-xl font-bold">Admin Panel</div>
        <div class="space-x-6">
            <a href="admin_panel.php" class="hover:text-yellow-400 transition">Admin Panel</a>
            <a href="logout.php" class="hover:text-yellow-400 transition">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="flex-grow flex items-center justify-center p-4">
        <div class="w-full max-w-2xl">
            <!-- Tabs -->
            <div class="flex space-x-4 mb-4">
                <button onclick="showTab('mail')" id="tabMail" class="tab-btn bg-yellow-500 text-black font-bold px-4 py-2 rounded">Send Mail</button>
                <button onclick="showTab('cash')" id="tabCash" class="tab-btn bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded">Send Cash Item</button>
            </div>

            <!-- Mail Form -->
            <div id="mailForm" class="tab-content bg-gray-900 p-6 rounded-lg shadow-lg">
                <?php if (!empty($mailMessage)): ?>
                    <div class="mb-4 p-4 rounded border transition-all fade-in 
                        <?php echo str_starts_with($mailMessage, 'Error') ? 'bg-red-800 border-red-600 text-red-200' : 'bg-green-800 border-green-600 text-green-200'; ?>">
                        <?php echo str_starts_with($mailMessage, 'Error') ? '? ' : '? '; ?>
                        <?php echo htmlspecialchars($mailMessage); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="grid gap-4 relative">
                    <input type="hidden" name="formType" value="mail">
                    <input type="number" name="charIdx" placeholder="ReceiverCharIdx" class="p-2 rounded bg-gray-800 text-white" required>
                    <input type="text" name="title" placeholder="Title" maxlength="40" class="p-2 rounded bg-gray-800 text-white" required>
                    <textarea name="content" placeholder="Content" maxlength="512" class="p-2 rounded bg-gray-800 text-white" required></textarea>

                    <div>
                        <label for="itemSearchMail" class="block mb-1 text-sm text-gray-400">Search Item:</label>
                        <input type="text" id="itemSearchMail" class="p-2 rounded bg-gray-800 text-white w-full" placeholder="Type item name..." autocomplete="off">
                        <ul id="resultsMail" class="absolute bg-white text-black w-full rounded mt-1 shadow-md max-h-48 overflow-auto hidden z-10"></ul>
                    </div>

                    <input type="number" id="itemKindIdxMail" name="itemKindIdx" placeholder="ItemKindIdx (autofilled)" class="p-2 rounded bg-gray-800 text-white" readonly>
                    <input type="number" name="itemOption" placeholder="ItemOption" class="p-2 rounded bg-gray-800 text-white">
                    <input type="number" name="itemDurationIdx" placeholder="ItemDurationIdx" class="p-2 rounded bg-gray-800 text-white">
                    <input type="number" name="expirationDay" placeholder="Expiration Days" class="p-2 rounded bg-gray-800 text-white">
                    <button type="submit" class="bg-yellow-400 text-black font-bold py-2 px-4 rounded hover:bg-yellow-500">Send Mail</button>
                </form>
            </div>

            <!-- Cash Form -->
            <div id="cashForm" class="tab-content hidden bg-gray-900 p-6 rounded-lg shadow-lg">
                <?php if (!empty($cashMessage)): ?>
                    <div class="mb-4 p-4 rounded border transition-all fade-in 
                        <?php echo str_starts_with($cashMessage, 'Error') ? 'bg-red-800 border-red-600 text-red-200' : 'bg-green-800 border-green-600 text-green-200'; ?>">
                        <?php echo str_starts_with($cashMessage, 'Error') ? '? ' : '? '; ?>
                        <?php echo htmlspecialchars($cashMessage); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="grid gap-4 relative">
                    <input type="hidden" name="formType" value="cash">

                    <div>
                        <label for="loginSearch" class="block mb-1 text-sm text-gray-400">Search Login ID:</label>
                        <input type="text" id="loginSearch" class="p-2 rounded bg-gray-800 text-white w-full" placeholder="Type login ID..." autocomplete="off">
                        <ul id="userResults" class="absolute bg-white text-black w-full rounded mt-1 shadow-md max-h-48 overflow-auto hidden z-10"></ul>
                    </div>

                    <input type="number" id="userNumField" name="userNum" placeholder="UserNum" class="p-2 rounded bg-gray-800 text-white" readonly required>

                    <div>
                        <label for="itemSearchCash" class="block mb-1 text-sm text-gray-400">Search Item:</label>
                        <input type="text" id="itemSearchCash" class="p-2 rounded bg-gray-800 text-white w-full" placeholder="Type item name..." autocomplete="off">
                        <ul id="resultsCash" class="absolute bg-white text-black w-full rounded mt-1 shadow-md max-h-48 overflow-auto hidden z-10"></ul>
                    </div>

                    <input type="number" id="itemIdxCash" name="itemIdx" placeholder="ItemIdx (autofilled)" class="p-2 rounded bg-gray-800 text-white" readonly>
                    <input type="number" name="itemOpt" placeholder="ItemOpt" class="p-2 rounded bg-gray-800 text-white">
                    <input type="number" name="durationIdx" placeholder="DurationIdx" class="p-2 rounded bg-gray-800 text-white">
                    <input type="text" name="memo" placeholder="Memo" class="p-2 rounded bg-gray-800 text-white">
                    <button type="submit" class="bg-yellow-400 text-black font-bold py-2 px-4 rounded hover:bg-yellow-500">Send Cash Item</button>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript for Tabs and Search -->
    <script>
        const tabButtons = {
            mail: document.getElementById('tabMail'),
            cash: document.getElementById('tabCash'),
        };

        function showTab(tab) {
            document.getElementById('mailForm').classList.add('hidden');
            document.getElementById('cashForm').classList.add('hidden');
            tabButtons.mail.classList.remove('bg-yellow-500', 'text-black');
            tabButtons.cash.classList.remove('bg-yellow-500', 'text-black');

            document.getElementById(tab + 'Form').classList.remove('hidden');
            tabButtons[tab].classList.add('bg-yellow-500', 'text-black');
        }

        let items = [];
        fetch('items.php')
            .then(res => res.json())
            .then(data => items = data);

        function setupItemSearch(inputId, resultId, outputId) {
            const input = document.getElementById(inputId);
            const results = document.getElementById(resultId);
            const output = document.getElementById(outputId);

            input.addEventListener('input', () => {
                const val = input.value.toLowerCase().trim();
                const filtered = items.filter(i => i.name.toLowerCase().includes(val)).slice(0, 10);

                results.innerHTML = '';
                results.classList.toggle('hidden', filtered.length === 0);

                filtered.forEach(item => {
                    const li = document.createElement('li');
                    li.textContent = `${item.name} (${item.id})`;
                    li.className = 'p-2 cursor-pointer';
                    li.onclick = () => {
                        input.value = item.name;
                        output.value = item.id;
                        results.classList.add('hidden');
                    };
                    results.appendChild(li);
                });
            });

            document.addEventListener('click', e => {
                if (!results.contains(e.target) && e.target !== input) {
                    results.classList.add('hidden');
                }
            });
        }

        setupItemSearch('itemSearchMail', 'resultsMail', 'itemKindIdxMail');
        setupItemSearch('itemSearchCash', 'resultsCash', 'itemIdxCash');

        // User search
        let users = [];
        fetch('/php/search_users.php')
            .then(res => res.json())
            .then(data => users = data)
            .catch(err => console.error("User fetch error:", err));

        const loginSearch = document.getElementById('loginSearch');
        const userResults = document.getElementById('userResults');
        const userNumField = document.getElementById('userNumField');

        loginSearch.addEventListener('input', () => {
            const search = loginSearch.value.toLowerCase();
            const filtered = users.filter(u => u.login.toLowerCase().includes(search)).slice(0, 10);

            userResults.innerHTML = '';
            userResults.classList.toggle('hidden', filtered.length === 0);

            filtered.forEach(user => {
                const li = document.createElement('li');
                li.textContent = `${user.login} (ID: ${user.id})`;
                li.className = 'p-2 cursor-pointer';
                li.onclick = () => {
                    loginSearch.value = user.login;
                    userNumField.value = user.id;
                    userResults.classList.add('hidden');
                };
                userResults.appendChild(li);
            });
        });

        document.addEventListener('click', e => {
            if (!userResults.contains(e.target) && e.target !== loginSearch) {
                userResults.classList.add('hidden');
            }
        });
    </script>
<script>
    // Reset forms on success (PHP injects a message if success occurs)
    window.addEventListener('DOMContentLoaded', () => {
        const mailMessage = <?php echo json_encode($mailMessage); ?>;
        const cashMessage = <?php echo json_encode($cashMessage); ?>;

        if (mailMessage && !mailMessage.startsWith('Error')) {
            document.querySelector('#mailForm form').reset();
            document.getElementById('itemKindIdxMail').value = '';
        }

        if (cashMessage && !cashMessage.startsWith('Error')) {
            document.querySelector('#cashForm form').reset();
            document.getElementById('itemIdxCash').value = '';
            document.getElementById('userNumField').value = '';
        }
    });
</script>
</body>
</html>
