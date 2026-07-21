<?php
require_once('../php/db.php');

// Function to get the user's IP address
function getUserIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = trim($_POST['user_id'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $name      = trim($_POST['name'] ?? '');
    $userkey   = trim($_POST['userkey'] ?? '');
    $refercode = trim($_POST['refercode'] ?? '');

    if (empty($id) || empty($password) || empty($email) || empty($name) || empty($userkey)) {
        $msg = "All fields except Referral Code are required.";
        header("Location: /registration/registration.html?msg=" . urlencode($msg));
        exit;
    }

    try {
        $ipAddress = getUserIp();

        // Check IP limit
        $checkStmt = $conn->prepare("
            SELECT COUNT(*) AS reg_count 
            FROM InviteTable 
            WHERE ip_address = :ip 
              AND CAST(CreatedAt AS DATE) = CAST(GETDATE() AS DATE)
        ");
        $checkStmt->bindParam(':ip', $ipAddress);
        $checkStmt->execute();
        $regInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($regInfo['reg_count'] >= 2) {
            $msg = "You can only register up to 2 accounts per day.";
            header("Location: /registration/registration.html?msg=" . urlencode($msg));
            exit;
        }

        // Register user
        $sql = "
            EXEC cabal_tool_registerAccount 
                @id = :id,
                @password = :password,
                @email = :email,
                @name = :name,
                @userkey = :userkey,
                @refercode = :refercode;
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':userkey', $userkey);
        $stmt->bindParam(':refercode', $refercode);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $usernum = $result['usernum'] ?? null;

        if ($usernum === null) {
            $msg = "Registration failed. No result returned.";
        } elseif ($usernum == -1) {
            $msg = "That Login ID is already in use.";
        } elseif ($usernum == -2) {
            $msg = "Something went wrong during registration.";
        } elseif ($usernum > 0) {
            // Use `id` as invite code
            $inviteCode = $id;

            // Save to InviteTable
            $insertInvite = $conn->prepare("
                INSERT INTO InviteTable (InviteCode, UserNum, ip_address, CreatedAt) 
                VALUES (:inviteCode, :usernum, :ip_address, GETDATE())
            ");
            $insertInvite->bindParam(':inviteCode', $inviteCode);
            $insertInvite->bindParam(':usernum', $usernum);
            $insertInvite->bindParam(':ip_address', $ipAddress);
            $insertInvite->execute();

            // Handle referral code if provided
            if (!empty($refercode)) {
                $stmt = $conn->prepare("SELECT UserNum FROM InviteTable WHERE InviteCode = :refercode");
                $stmt->bindParam(':refercode', $refercode);
                $stmt->execute();
                $referrer = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($referrer) {
                    $inviterUserNum = $referrer['UserNum'];
                    $insertRedemption = $conn->prepare("
                        INSERT INTO InviteRedemptions 
                        (InviteCode, InviterUserNum, InvitedUserNum, RedeemedAt, Status) 
                        VALUES (:refercode, :inviterUserNum, :invitedUserNum, GETDATE(), 'Pending')
                    ");
                    $insertRedemption->bindParam(':refercode', $refercode);
                    $insertRedemption->bindParam(':inviterUserNum', $inviterUserNum);
                    $insertRedemption->bindParam(':invitedUserNum', $usernum);
                    $insertRedemption->execute();
                } else {
                    $msg = "Invalid referral code.";
                    header("Location: /registration/registration.html?msg=" . urlencode($msg));
                    exit;
                }
            }

            $msg = "Registration successful! Your invite code is: <strong>$inviteCode</strong>. You may now log in.";
            header("Location: /login.php?msg=" . urlencode($msg));
            exit;
        } else {
            $msg = "Unknown error occurred.";
        }

    } catch (PDOException $e) {
        error_log("Registration failed: " . $e->getMessage());
        $msg = "Registration failed. Please try again later.";
    }

    header("Location: /registration/registration.html?msg=" . urlencode($msg));
    exit;
}
?>
