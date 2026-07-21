<?php
// Database credentials
$serverName = "localhost";
$database = "CabalCash";
$accountDb = "Account";
$username = "sa";
$password = "p6i6FFC6RZCD4jmR71xB";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $loginId = $_POST["loginid"] ?? '';
    $itemIdx = $_POST["itemidx"] ?? 0;
    $itemOpt = $_POST["itemopt"] ?? 0;
    $durationIdx = $_POST["durationidx"] ?? 0;
    $serverIdx = 1;
    $memo = '1';
    $tranNo = rand(1000, 99999);

    try {
        // Connect to Account DB to get UserNum
        $accountConn = new PDO("sqlsrv:Server=$serverName;Database=$accountDb", $username, $password);
        $accountConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $accountConn->prepare("EXEC dbo.cabal_tool_GetUserNum @ID = :id");
        $stmt->bindParam(":id", $loginId);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !isset($user["UserNum"])) {
            throw new Exception("User not found with ID: $loginId");
        }
        $userNum = $user["UserNum"];

        // Connect to CabalCash DB to add item
        $cashConn = new PDO("sqlsrv:Server=$serverName;Database=$database", $username, $password);
        $cashConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $cashConn->prepare("EXEC dbo.up_AddMyCashItemByItem 
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
        $message = "✅ Item successfully added to user!";
    } catch (PDOException $e) {
        $message = "❌ Error: " . $e->getMessage();
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Cash Item</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            padding-top: 50px;
        }
        .form-container {
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 400px;
        }
        h2 {
            margin-bottom: 1rem;
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 1rem;
        }
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        input[type="submit"] {
            background: #28a745;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 1.5rem;
        }
        input[type="submit"]:hover {
            background: #218838;
        }
        .message {
            margin-top: 1rem;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Add Cash Item</h2>
    <form method="POST">
        <label for="loginid">Login ID:</label>
        <input type="text" name="loginid" required>

        <label for="itemidx">ItemIdx:</label>
        <input type="number" name="itemidx" value="2366" required>

        <label for="itemopt">ItemOpt:</label>
        <input type="number" name="itemopt" value="99999" required>

        <label for="durationidx">DurationIdx:</label>
        <input type="number" name="durationidx" value="0" required>

        <input type="submit" value="Add Item">
    </form>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
</div>

</body>
</html>
