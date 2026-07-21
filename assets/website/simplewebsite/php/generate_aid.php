<?php
// generate_aid.php

function generateAid($usernum) {
    // Assuming you have a way to fetch the user's real ID based on usernum
    global $conn;
    try {
        // Fetch the real user ID from your database (adjust your query as needed)
        $stmt = $conn->prepare("EXEC dbo.cabal_tool_userID_get :usernum");
        $stmt->bindParam(':usernum', $usernum, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && !empty($result['ID'])) {
            $realId = strtoupper($result['ID']); // Convert to uppercase for consistency
            
            // Generate a hash-based prefix using sha256 (adjust length as needed)
            $hashPrefix = strtoupper(substr(hash('sha256', $realId . $usernum), 0, 16));
            
            // Return the final formatted AID
            return $hashPrefix . $realId;
        } else {
            return null;  // Handle error if the realId is not found
        }
    } catch (PDOException $e) {
        // Handle database error
        echo "Error fetching real user ID: " . $e->getMessage();
        return null;
    }
}
?>
