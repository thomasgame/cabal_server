<?php
session_start();
require_once 'db.php';


$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $charIdx = intval($_POST['charIdx']);
    $title = $_POST['title'];
    $content = $_POST['content'];
    $itemKindIdx = intval($_POST['itemKindIdx']);
    $itemOption = intval($_POST['itemOption']);
    $itemDurationIdx = intval($_POST['itemDurationIdx']);
    $expirationDay = intval($_POST['expirationDay']);

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
            ':charIdx' => $charIdx,
            ':title' => $title,
            ':content' => $content,
            ':itemKindIdx' => $itemKindIdx,
            ':itemOption' => $itemOption,
            ':itemDurationIdx' => $itemDurationIdx,
            ':expirationDay' => $expirationDay
        ]);

        $message = "? Mail sent successfully!";
    } catch (PDOException $e) {
        $message = "? Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Mail - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(to bottom, #111827, #000000); }
        #results li:hover { background-color: #facc15; color: black; }
    </style>
</head>
<body class="text-white font-sans min-h-screen">

    <div class="flex justify-center items-start py-10 px-4">
        <div class="w-full max-w-xl">

            <h1 class="text-3xl font-bold mb-6 text-center">Send In-Game Mail</h1>

            <?php if ($message): ?>
                <div class="mb-4 p-4 rounded bg-gray-800 border border-gray-700 text-sm">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="grid gap-4 bg-gray-900 p-6 rounded-lg shadow-lg">
                <input type="number" name="charIdx" placeholder="ReceiverCharIdx" class="p-2 rounded bg-gray-800 text-white" required>
                <input type="text" name="title" placeholder="Title" class="p-2 rounded bg-gray-800 text-white" maxlength="40" required>
                <textarea name="content" placeholder="Content" class="p-2 rounded bg-gray-800 text-white" maxlength="512" required></textarea>

                <div>
                    <label for="itemSearch" class="block mb-1 text-sm text-gray-400">Search Item:</label>
                    <input type="text" id="itemSearch" class="p-2 rounded bg-gray-800 text-white w-full" placeholder="Type item name..." autocomplete="off">
                    <ul id="results" class="absolute bg-white text-black w-full rounded mt-1 shadow-md max-h-48 overflow-auto hidden z-10"></ul>
                </div>

                <input type="number" id="itemKindIdx" name="itemKindIdx" placeholder="ItemKindIdx (autofilled or 0)" class="p-2 rounded bg-gray-800 text-white" readonly>
                <input type="number" name="itemOption" placeholder="ItemOption (0 if none)" class="p-2 rounded bg-gray-800 text-white">
                <input type="number" name="itemDurationIdx" placeholder="ItemDurationIdx (0 if none)" class="p-2 rounded bg-gray-800 text-white">
                <input type="number" name="expirationDay" placeholder="Expiration Days" class="p-2 rounded bg-gray-800 text-white">

                <button type="submit" class="bg-yellow-400 text-black font-bold py-2 px-4 rounded hover:bg-yellow-500">Send Mail</button>
            </form>
        </div>
    </div>

    <script>
    let items = [];

    fetch('items.php')
        .then(res => res.json())
        .then(data => items = data)
        .catch(err => console.error("Failed to load items:", err));

    const searchInput = document.getElementById('itemSearch');
    const resultBox = document.getElementById('results');
    const itemKindInput = document.getElementById('itemKindIdx');

    searchInput.addEventListener('input', () => {
        const search = searchInput.value.trim().toLowerCase();
        const filtered = items
            .filter(item => item.name.toLowerCase().includes(search))
            .slice(0, 10);

        resultBox.innerHTML = '';
        resultBox.classList.toggle('hidden', filtered.length === 0);

        filtered.forEach(item => {
            const li = document.createElement('li');
            li.textContent = `${item.name} (${item.id})`;
            li.className = "p-2 cursor-pointer";
            li.onclick = () => {
                searchInput.value = item.name;
                itemKindInput.value = item.id.replace(/^item/, '');
                resultBox.classList.add('hidden');
            };
            resultBox.appendChild(li);
        });
    });

    document.addEventListener('click', (e) => {
        if (!resultBox.contains(e.target) && e.target !== searchInput) {
            resultBox.classList.add('hidden');
        }
    });
    </script>

</body>
</html>