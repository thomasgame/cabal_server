<?php
function getUserFinancials($usernum, $connCash, $connServer) {
    $aid = ''; // Default value
    $balance = 0;
    $forcegemHave = 0;

    if ($usernum <= 0) {
        throw new Exception("Invalid usernum");
    }

    try {
        // Fetch cash balance
        $stmt = $connCash->prepare("EXEC up_GetUserCashInfo :usernum");
        $stmt->bindParam(':usernum', $usernum, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) $balance = $result['CashBonus'];

        // Fetch ForceGem balance
        $stmt = $connServer->prepare("EXEC dbo.cabal_tool_forcegem_get :usernum");
        $stmt->bindParam(':usernum', $usernum, PDO::PARAM_INT);
        $stmt->execute();
        $forcegemResult = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($forcegemResult) $forcegemHave = $forcegemResult['ForcegemHave'];

        // Generate AID
        $aid = 'AID-' . strtoupper(substr(md5($usernum), 0, 12));

        // Return all values
        return [
            'aid' => $aid,
            'balance' => $balance,
            'forcegemHave' => $forcegemHave
        ];

    } catch (PDOException $e) {
        throw new Exception("Error fetching financial data: " . $e->getMessage());
    }
}
?>
