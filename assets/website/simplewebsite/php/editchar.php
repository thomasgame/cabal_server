<?php
// Database connection credentials
$serverName = "localhost";  // Database server
$database = "Server01";     // Database name
$username = "sa";  // Replace with your SQL Server username
$password = "YourStr0NgP4sSwoRD";  // Replace with your SQL Server password

try {
    // Create connection using PDO for SQL Server
    $pdo = new PDO("sqlsrv:server=$serverName;database=$database", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

// Function to get characters by name (with LIKE query for partial matching)
function getCharactersByName($name) {
    global $pdo;
    $sql = "SELECT CharacterIdx, Name FROM dbo.cabal_character_table WHERE Name LIKE :name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['name' => '%' . $name . '%']);
    return $stmt->fetchAll();
}

// Check if a character ID is provided for editing
if (isset($_GET['CharacterIdx'])) {
    $characterIdx = $_GET['CharacterIdx'];

    // Fetch character data from the database
    $sql = "SELECT * FROM dbo.cabal_character_table WHERE CharacterIdx = :CharacterIdx";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['CharacterIdx' => $characterIdx]);
    $character = $stmt->fetch();

    if (!$character) {
        echo "Character not found.";
        exit;
    }
} elseif (isset($_POST['search'])) {
    // Handle search query and get characters by name
    $characters = getCharactersByName($_POST['name']);
} else {
    $characters = [];
}

// Handle form submission to update the character data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    // Prepare the update query
$updateSQL = "UPDATE dbo.cabal_character_table SET
                LEV = :LEV, EXP = :EXP, STR = :STR, DEX = :DEX, INT = :INT, 
                PNT = :PNT, Style = :Style, SwdPNT = :SwdPNT, 
                MagPNT = :MagPNT, RankEXP = :RankEXP, RP = :RP, 
                Reputation = :Reputation, Nation = :Nation
              WHERE CharacterIdx = :CharacterIdx";


    // Prepare the statement
    $stmt = $pdo->prepare($updateSQL);

    // Bind form inputs to the statement
$stmt->execute([
    'CharacterIdx' => $characterIdx,
    'LEV' => $_POST['LEV'],
    'EXP' => $_POST['EXP'],
    'STR' => $_POST['STR'],
    'DEX' => $_POST['DEX'],
    'INT' => $_POST['INT'],
    'PNT' => $_POST['PNT'],
    'Style' => $_POST['Style'],
    'SwdPNT' => $_POST['SwdPNT'],
    'MagPNT' => $_POST['MagPNT'],
    'RankEXP' => $_POST['RankEXP'],
    'RP' => $_POST['RP'],
    'Reputation' => $_POST['Reputation'],
    'Nation' => $_POST['Nation']
]);


    echo "<div class='alert alert-success'>Character data updated successfully!</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Character</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
            color: #333;
        }

        header {
            background-color: #db2626;
            padding: 20px;
            text-align: center;
            color: white;
            font-size: 24px;
        }

        .container {
            width: 80%;
            margin: 20px auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        input[type="text"], input[type="number"], select {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        input[type="submit"] {
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .alert {
            padding: 10px;
            margin: 10px 0;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
            text-align: center;
        }

        .alert-success {
            background-color: #28a745;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        select {
            padding: 10px;
            font-size: 16px;
        }

        .no-results {
            color: red;
            text-align: center;
        }

        .search-container {
            text-align: center;
        }

        .search-container input[type="text"] {
            width: 40%;
        }

        .search-container input[type="submit"] {
            width: 20%;
        }
    </style>
</head>
<body>

<header>
    GO TO CHARACTER SELECT INGAME OR IT DOESNT WORK
</header>

<div class="container">
    <h1>Search and Edit Character</h1>

    <!-- Character Search Form -->
    <div class="search-container">
        <form method="POST">
            <label for="name">Which character do you want to edit? Please insert the character name below:</label><br>
            <input type="text" name="name" id="name" required>
            <input type="submit" name="search" value="Search">
        </form>
    </div>

    <?php if (isset($_POST['search'])): ?>
        <?php if (count($characters) > 0): ?>
            <h2>Select a Character</h2>
            <form method="GET">
                <select name="CharacterIdx" required>
                    <?php foreach ($characters as $char): ?>
                        <option value="<?php echo $char['CharacterIdx']; ?>"><?php echo htmlspecialchars($char['Name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" value="Edit Selected Character">
            </form>
        <?php else: ?>
            <p class="no-results">No characters found with that name.</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($character)): ?>
        <h2>Edit Character: <?php echo htmlspecialchars($character['Name']); ?></h2>
<form method="POST">
    <input type="hidden" name="update" value="1">
    <input type="hidden" name="CharacterIdx" value="<?php echo $character['CharacterIdx']; ?>">

    <label for="LEV">Level:</label>
    <input type="number" name="LEV" value="<?php echo $character['LEV']; ?>" required><br>

    <label for="EXP">Experience:</label>
    <input type="number" name="EXP" value="<?php echo $character['EXP']; ?>" required><br>

    <label for="STR">Strength:</label>
    <input type="number" name="STR" value="<?php echo $character['STR']; ?>" required><br>

    <label for="DEX">Dexterity:</label>
    <input type="number" name="DEX" value="<?php echo $character['DEX']; ?>" required><br>

    <label for="INT">Intelligence:</label>
    <input type="number" name="INT" value="<?php echo $character['INT']; ?>" required><br>

    <label for="PNT">Points:</label>
    <input type="number" name="PNT" value="<?php echo $character['PNT']; ?>" required><br>

    <label for="Style">Style:</label>
    <input type="number" name="Style" value="<?php echo $character['Style']; ?>" required><br>

    <label for="SwdPNT">Sword Points:</label>
    <input type="number" name="SwdPNT" value="<?php echo $character['SwdPNT']; ?>" required><br>

    <label for="MagPNT">Magic Points:</label>
    <input type="number" name="MagPNT" value="<?php echo $character['MagPNT']; ?>" required><br>

    <label for="RankEXP">Rank Experience:</label>
    <input type="number" name="RankEXP" value="<?php echo $character['RankEXP']; ?>" required><br>

    <label for="RP">RP:</label>
    <input type="number" name="RP" value="<?php echo $character['RP']; ?>" required><br>

    <label for="Reputation">Reputation:</label>
    <input type="number" name="Reputation" value="<?php echo $character['Reputation']; ?>" required><br>

    <label for="Nation">Nation:</label>
    <input type="number" name="Nation" value="<?php echo $character['Nation']; ?>" required><br>

    <input type="submit" value="Update Character">
</form>

    <?php endif; ?>
</div>

</body>
</html>
