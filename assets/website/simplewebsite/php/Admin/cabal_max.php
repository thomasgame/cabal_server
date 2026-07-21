<?php
session_start();
require_once 'db.php'; // Make sure this creates a $conn (PDO instance)

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["CharacterIdx"])) {
    $characterIdx = intval($_POST["CharacterIdx"]);

    try {
        $stmt = $connServer->prepare("EXEC dbo.cabal_MAX @CharacterIdx = :CharacterIdx");
        $stmt->bindParam(':CharacterIdx', $characterIdx, PDO::PARAM_INT);
        $stmt->execute();

        $message = "<span style='color: green;'>Successfully executed cabal_MAX for CharacterIdx: $characterIdx</span>";
    } catch (PDOException $e) {
        $message = "<span style='color: red;'>Error: " . $e->getMessage() . "</span>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Execute cabal_MAX</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-4">Execute <code>cabal_MAX</code> Procedure</h2>

        <?php if (!empty($message)): ?>
            <div class="mb-4"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <label class="block mb-2 font-medium" for="CharacterIdx">CharacterIdx:</label>
            <input type="number" name="CharacterIdx" id="CharacterIdx" required class="w-full p-2 border rounded mb-4">

            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                Execute
            </button>
        </form>
    </div>
</body>
</html>
