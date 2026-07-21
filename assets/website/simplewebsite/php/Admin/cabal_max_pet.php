<?php
session_start();
require_once 'db.php'; // Must define $conn = new PDO(...)

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["PetID"])) {
    $petID = intval($_POST["PetID"]);

    try {
        $stmt = $connServer->prepare("EXEC dbo.cabal_MAX_pet @PetID = :PetID");
        $stmt->bindParam(':PetID', $petID, PDO::PARAM_INT);
        $stmt->execute();

        $message = "<span class='text-green-600 font-semibold'>Pet ID $petID updated successfully.</span>";
    } catch (PDOException $e) {
        $message = "<span class='text-red-600 font-semibold'>Error: " . $e->getMessage() . "</span>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Execute cabal_MAX_pet</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Execute <code>cabal_MAX_pet</code></h2>

        <?php if (!empty($message)): ?>
            <div class="mb-4"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <label class="block mb-2 font-medium" for="PetID">Pet Serial (PetID):</label>
            <input type="number" name="PetID" id="PetID" required class="w-full p-2 border rounded mb-4" placeholder="Enter Pet Serial ID">

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded">
                Execute Procedure
            </button>
        </form>
    </div>
</body>
</html>