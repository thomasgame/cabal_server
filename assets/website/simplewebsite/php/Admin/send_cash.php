<?php
session_start();
require_once 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $loginId = $_POST["loginid"] ?? '';
    $itemIdxList = $_POST["itemidx"] ?? [];
    $itemOptList = $_POST["itemopt"] ?? [];
    $durationIdxList = $_POST["durationidx"] ?? [];
    $countList = $_POST["count"] ?? [];
    $serverIdx = 1;
    $memo = $_POST["memo"] ?? 'admin';

    try {
        $stmt = $conn->prepare("EXEC dbo.cabal_tool_GetUserNum @ID = :id");
        $stmt->bindParam(":id", $loginId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !isset($user["UserNum"])) {
            throw new Exception("User not found with ID: $loginId");
        }

        $userNum = $user["UserNum"];
        $totalSent = 0;

        for ($i = 0; $i < count($itemIdxList); $i++) {
            $itemIdx = intval($itemIdxList[$i]);
            $itemOpt = intval($itemOptList[$i]);
            $durationIdx = intval($durationIdxList[$i]);
            $count = max(1, intval($countList[$i]));

            for ($j = 0; $j < $count; $j++) {
                $tranNo = time() + ($i * 100 + $j);

                $stmt = $connCash->prepare("EXEC dbo.up_AddMyCashItemByItem 
                    @UserNum = :usernum,
                    @TranNo = :tranno,
                    @ServerIdx = :serveridx,
                    @ItemIdx = :itemidx,
                    @ItemOpt = :itemopt,
                    @DurationIdx = :durationidx,
                    @Memo = :memo");

                $stmt->bindParam(":usernum", $userNum);
                $stmt->bindParam(":tranno", $tranNo);
                $stmt->bindParam(":serveridx", $serverIdx);
                $stmt->bindParam(":itemidx", $itemIdx);
                $stmt->bindParam(":itemopt", $itemOpt);
                $stmt->bindParam(":durationidx", $durationIdx);
                $stmt->bindParam(":memo", $memo);
                $stmt->execute();

                $totalSent++;
            }
        }

        $message = "? Successfully sent $totalSent item(s) to $loginId!";
    } catch (Exception $e) {
        $message = "? Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Cash Item - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(to bottom, #111827, #000000); }
    </style>
</head>
<body class="text-white font-sans min-h-screen">

    <!-- Nav -->
    <nav class="bg-black text-white flex justify-between items-center px-8 py-4 border-b border-gray-900">
        <div class="text-xl font-bold">Admin Panel</div>
        <div class="space-x-6">
            <a href="dashboard.php" class="hover:text-yellow-400 transition">Dashboard</a>
            <a href="logout.php" class="hover:text-yellow-400 transition">Logout</a>
        </div>
    </nav>

    <div class="flex justify-center items-start py-10 px-4">
        <div class="w-full max-w-2xl">
            <h1 class="text-3xl font-bold mb-6 text-center">Send Cash Items</h1>

            <?php if ($message): ?>
                <div class="mb-4 p-4 rounded bg-gray-800 border border-gray-700 text-sm">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="grid gap-4 bg-gray-900 p-6 rounded-lg shadow-lg">
                <input type="text" name="loginid" placeholder="User ID (loginID)" class="p-2 rounded bg-gray-800 text-white" required>

                <!-- Items Container -->
                <div id="itemsContainer" class="space-y-2">
                    <div class="item-group grid grid-cols-6 gap-2 relative">
                        <div class="col-span-2 relative">
                            <input type="text" placeholder="Search item..." class="item-search p-2 rounded bg-gray-800 text-white w-full" autocomplete="off">
                            <div class="search-results absolute top-full left-0 right-0 bg-gray-900 border border-gray-700 max-h-40 overflow-y-auto hidden z-50"></div>
                        </div>
                        <input type="number" name="itemidx[]" placeholder="ItemIdx" class="itemidx p-2 rounded bg-gray-800 text-white" required>
                        <input type="number" name="itemopt[]" placeholder="ItemOpt" class="p-2 rounded bg-gray-800 text-white" required>
                        <input type="number" name="durationidx[]" placeholder="DurationIdx" class="p-2 rounded bg-gray-800 text-white" required>
                        <input type="number" name="count[]" placeholder="Count" class="p-2 rounded bg-gray-800 text-white" min="1" value="1" required>
                    </div>
                </div>

                <button type="button" onclick="addItemRow()" class="text-sm text-yellow-400">+ Add Another Item</button>

                <input type="text" name="memo" placeholder="Memo (optional)" class="p-2 rounded bg-gray-800 text-white">

                <button type="submit" class="bg-yellow-400 text-black font-bold py-2 px-4 rounded hover:bg-yellow-500">Send Cash Items</button>
            </form>
        </div>
    </div>

    <script>
    function addItemRow() {
        const container = document.getElementById('itemsContainer');
        const group = document.createElement('div');
        group.className = 'item-group grid grid-cols-6 gap-2 relative';
        group.innerHTML = `
            <div class="col-span-2 relative">
                <input type="text" placeholder="Search item..." class="item-search p-2 rounded bg-gray-800 text-white w-full" autocomplete="off">
                <div class="search-results absolute top-full left-0 right-0 bg-gray-900 border border-gray-700 max-h-40 overflow-y-auto hidden z-50"></div>
            </div>
            <input type="number" name="itemidx[]" placeholder="ItemIdx" class="itemidx p-2 rounded bg-gray-800 text-white" required>
            <input type="number" name="itemopt[]" placeholder="ItemOpt" class="p-2 rounded bg-gray-800 text-white" required>
            <input type="number" name="durationidx[]" placeholder="DurationIdx" class="p-2 rounded bg-gray-800 text-white" required>
            <input type="number" name="count[]" placeholder="Count" class="p-2 rounded bg-gray-800 text-white" min="1" value="1" required>
        `;
        container.appendChild(group);
        setupSearch(group);
    }

    function setupSearch(group) {
        const searchInput = group.querySelector('.item-search');
        const itemIdxInput = group.querySelector('.itemidx');
        const resultsBox = group.querySelector('.search-results');

        let timeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(timeout);
            const query = searchInput.value.trim().toLowerCase();
            if (query.length < 2) {
                resultsBox.classList.add('hidden');
                return;
            }

            timeout = setTimeout(() => {
                fetch('items.php?q=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        resultsBox.innerHTML = '';
                        if (!Array.isArray(data) || data.length === 0) {
                            resultsBox.innerHTML = '<div class="p-2 text-gray-400">No items found</div>';
                        } else {
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'p-2 hover:bg-gray-700 cursor-pointer';
                                div.textContent = `${item.name} (ID: ${item.id})`;
                                div.addEventListener('click', () => {
                                    itemIdxInput.value = item.id;
                                    searchInput.value = item.name;
                                    resultsBox.classList.add('hidden');
                                });
                                resultsBox.appendChild(div);
                            });
                        }
                        resultsBox.classList.remove('hidden');
                    });
            }, 300);
        });

        document.addEventListener('click', (e) => {
            if (!group.contains(e.target)) {
                resultsBox.classList.add('hidden');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        setupSearch(document.querySelector('.item-group'));
    });
    </script>

</body>
</html>
