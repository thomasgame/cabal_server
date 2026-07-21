<!DOCTYPE html>
<html>
<head>
    <title>Call cabal_MAX Procedure</title>
</head>
<body>
    <h2>Activate Pet for Character</h2>
    <form method="post">
        <label for="CharacterIdx">CharacterIdx:</label><br>
        <input type="number" id="CharacterIdx" name="CharacterIdx" required><br><br>

        <label for="petid">Pet ID:</label><br>
        <input type="number" id="petid" name="petid" required><br><br>

        <input type="submit" value="Submit">
    </form>

<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $characterIdx = $_POST['CharacterIdx'];
    $petId = $_POST['petid'];

    require 'db.php'; // include your connection

    try {
        $stmt = $conn->prepare("EXEC cabal_MAX @CharacterIdx = :charIdx, @petid = :petId");
        $stmt->bindParam(':charIdx', $characterIdx, PDO::PARAM_INT);
        $stmt->bindParam(':petId', $petId, PDO::PARAM_INT);
        $stmt->execute();

        echo "<p style='color:green;'>Procedure executed successfully.</p>";

        // Optional: If your SP returns data
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($results)) {
            echo "<pre>";
            print_r($results);
            echo "</pre>";
        }

    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>
</body>
</html>
