<?php
// --- 1. CONFIGURATION & DATA FETCH ---
// Assuming $conn, $username, and $ecoin are already defined.

$message = "";
$msgType = "";

try {
    // Removed TwoFactorSecret from query as it is no longer used for verification
    $stmtStatus = $conn->prepare("SELECT Login, Email, [UserNum], [Password] FROM Account.dbo.cabal_auth_table WHERE ID = :id");
    $stmtStatus->execute(['id' => $username]);
    $dbUserData = $stmtStatus->fetch(PDO::FETCH_ASSOC);

    $isOnline   = ($dbUserData && $dbUserData['Login'] == 1);
    $email      = ($dbUserData['Email'] == "NULL" || empty($dbUserData['Email'])) ? null : $dbUserData['Email'];
    $UserNum    = $dbUserData['UserNum'] ?? 0;
    $storedPass = $dbUserData['Password'] ?? '';

    // Mask Email (e.g., u****e@email.com)
    $maskedEmail = "";
    if ($email) {
        $em = explode("@", $email);
        $name = $em[0];
        $len = strlen($name);
        $maskedEmail = ($len <= 2) ? $name : substr($name, 0, 1) . str_repeat('*', $len - 2) . substr($name, -1);
        $maskedEmail .= "@" . $em[1];
    }

} catch (Exception $e) {
    $isOnline = false;
}

// --- 2. FORM PROCESSING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ACTION: Setup Initial Email
    if (isset($_POST['set_initial_email'])) {
        $newEmail = $_POST['init_email'] ?? '';
        if (filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $stmt = $conn->prepare("UPDATE Account.dbo.cabal_auth_table SET Email = :email WHERE ID = :id");
            $stmt->execute(['email' => $newEmail, 'id' => $username]);
            $email = $newEmail;
            $message = "Email linked successfully!"; $msgType = "success";
        } else { $message = "Invalid email format."; $msgType = "danger"; }
    }

    // DASHBOARD ACTIONS
    elseif ($email) {
        // Change Email - OTP CHECK REMOVED
        if (isset($_POST['change_email'])) {
            if ($isOnline) { 
                $message = "Logout from game first."; 
                $msgType = "danger"; 
            } else {
                $stmt = $conn->prepare("UPDATE Account.dbo.cabal_auth_table SET Email = :m WHERE ID = :id");
                $stmt->execute(['m' => $_POST['new_email'], 'id' => $username]);
                $email = $_POST['new_email']; 
                $message = "Email updated!"; 
                $msgType = "success";
            }
        }
        
        // Change Password
        if (isset($_POST['change_password'])) {
            $currPass = $_POST['curr_pass'];
            $newPass = $_POST['new_pass'];
            $verifyPass = $_POST['verify_pass'];
            $inputEmail = $_POST['verify_email'];

            $stmtCheck = $conn->prepare("SELECT PWDCOMPARE(:p, [Password]) as is_match FROM Account.dbo.cabal_auth_table WHERE ID = :id");
            $stmtCheck->execute(['p' => $currPass, 'id' => $username]);
            $passCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($isOnline) { $message = "Logout first."; $msgType = "danger"; }
            elseif ($newPass !== $verifyPass) { $message = "New passwords do not match."; $msgType = "danger"; }
            elseif ($inputEmail !== $email) { $message = "Verification email is incorrect."; $msgType = "danger"; }
            elseif (!$passCheck || $passCheck['is_match'] != 1) { $message = "Current password incorrect."; $msgType = "danger"; }
            else {
                $stmt = $conn->prepare("UPDATE Account.dbo.cabal_auth_table SET [Password] = PWDENCRYPT(:p) WHERE ID = :id");
                $stmt->execute(['p' => $newPass, 'id' => $username]);
                $message = "Password updated successfully!"; $msgType = "success";
            }
        }

        // Sub-Password Reset
        if (isset($_POST['reset_subpass'])) {
            if ($isOnline) { $message = "Logout first."; $msgType = "danger"; }
            elseif ($ecoin < 500) { $message = "Insufficient ECOIN."; $msgType = "danger"; }
            else {
                $conn->beginTransaction();
                $conn->prepare("UPDATE CabalCash.dbo.CashAccount SET Cash = Cash - 500 WHERE UserNum = :un")->execute(['un' => $UserNum]);
                $conn->prepare("UPDATE Account.dbo.cabal_sub_password_table SET [CharPassword]=NULL,[WareHousePassword]=NULL,[EquipmentPassword]=NULL WHERE UserNum = :un")->execute(['un' => $UserNum]);
                $conn->commit(); 
                $ecoin -= 500; $message = "Sub-passwords have been cleared."; $msgType = "success";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Cabal Server</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg-primary: #0a0e1a;
            --bg-secondary: #111827;
            --bg-tertiary: #1a1f35;
            --card-bg: linear-gradient(135deg, #1e293b 0%, #1a1f35 100%);
            --border-color: #2d3748;
            --border-accent: #3b82f6;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent-blue: #3b82f6;
            --accent-blue-dark: #2563eb;
            --accent-gold: #f59e0b;
            --accent-gold-dark: #d97706;
            --danger: #ef4444;
            --danger-dark: #dc2626;
            --success: #10b981;
            --success-dark: #059669;
            --input-bg: #0f172a;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(245, 158, 11, 0.05) 0px, transparent 50%);
        }

        .settings-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 30px; font-weight: 500; display: flex; align-items: center; gap: 12px; box-shadow: var(--shadow-lg); }
        .alert-danger { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; }
        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #86efac; }

        .page-header { margin-bottom: 40px; }
        .page-title { font-size: 2.5rem; font-weight: 700; background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 8px; }
        .page-subtitle { color: var(--text-secondary); font-size: 1.1rem; }

        .account-banner { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: var(--shadow-lg); position: relative; overflow: hidden; }
        .account-banner::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--accent-blue), var(--accent-gold)); }
        
        .balance-card { text-align: right; background: rgba(59, 130, 246, 0.05); padding: 20px 30px; border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.2); }
        .balance-amount { font-size: 2rem; font-weight: 700; background: linear-gradient(135deg, var(--accent-gold), var(--accent-gold-dark)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px; }
        .dashboard-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; box-shadow: var(--shadow-lg); transition: all 0.3s ease; position: relative; overflow: hidden; }
        .card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color); }
        .card-title { font-size: 1.3rem; font-weight: 600; }

        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; color: var(--text-secondary); font-size: 0.9rem; font-weight: 500; text-transform: uppercase; }
        .form-input { width: 100%; padding: 14px 16px; background: var(--input-bg); border: 2px solid var(--border-color); border-radius: 10px; color: var(--text-primary); font-size: 1rem; }
        .masked-box { background: var(--input-bg); padding: 16px; border-radius: 10px; border: 2px dashed var(--border-color); color: var(--accent-blue); text-align: center; font-weight: 600; }

        .btn { width: 100%; padding: 16px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px; }
        .btn-primary { background: linear-gradient(135deg, var(--accent-blue), var(--accent-blue-dark)); color: #ffffff; }
        .btn-danger { background: linear-gradient(135deg, var(--danger), var(--danger-dark)); color: #ffffff; }

        .status-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .status-online { background: rgba(16, 185, 129, 0.15); color: var(--success); }
        .status-offline { background: rgba(100, 116, 139, 0.15); color: var(--text-secondary); }

        .info-box { background: rgba(59, 130, 246, 0.1); padding: 16px 20px; border-radius: 10px; margin: 20px 0; color: var(--text-secondary); }
        .warning-box { background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--danger); padding: 16px 20px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="settings-container">
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $msgType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!$email): ?>
            <div class="dashboard-card" style="max-width: 500px; margin: 50px auto; text-align: center;">
                <h2 class="card-title">Register Email Address</h2>
                <p style="color: var(--text-secondary); margin-bottom: 30px;">Provide a valid email for account management</p>
                <form method="POST">
                    <div class="form-group">
                        <input type="email" name="init_email" class="form-input" placeholder="your.email@example.com" required>
                    </div>
                    <button type="submit" name="set_initial_email" class="btn btn-primary">Save Email Address</button>
                </form>
            </div>

        <?php else: ?>
            <div class="page-header">
                <h1 class="page-title">Account Management</h1>
                <p class="page-subtitle">Security settings for <strong><?= htmlspecialchars($username) ?></strong></p>
            </div>

            <div class="account-banner">
                <div class="account-info">
                    <h2>Welcome!</h2>
                    <?php if ($isOnline): ?>
                        <div class="status-badge status-online">Online</div>
                    <?php else: ?>
                        <div class="status-badge status-offline">Offline</div>
                    <?php endif; ?>
                </div>
                
                <div class="balance-card">
                    <span style="font-size: 0.85rem; color: var(--text-secondary);">Available Balance</span>
                    <div>
                        <span class="balance-amount"><?= number_format($ecoin ?? 0) ?></span>
                        <span style="font-weight: 500;"> ECOIN</span>
                    </div>
                </div>
            </div>

            <div class="card-grid">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">Update Email</h3>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Current Email</label>
                        <div class="masked-box"><?= htmlspecialchars($maskedEmail) ?></div>
                    </div>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">New Email Address</label>
                            <input type="email" name="new_email" class="form-input" placeholder="new.email@example.com" required>
                        </div>
                        <button type="submit" name="change_email" class="btn btn-primary">Update Email Now</button>
                    </form>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">Change Password</h3>
                    </div>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Verify Email</label>
                            <input type="email" name="verify_email" class="form-input" placeholder="Confirm your email" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="curr_pass" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_pass" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="verify_pass" class="form-input" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">Reset Sub-Passwords</h3>
                    </div>
                    <div class="info-box">Clears Character PIN, Warehouse, and Equipment Passwords.</div>
                    <div class="warning-box"><div style="color: #fca5a5; font-weight: 600;">Cost: 500 ECOIN</div></div>
                    <form method="POST" onsubmit="return confirm('Spend 500 ECOIN to reset PINs?');">
                        <button type="submit" name="reset_subpass" class="btn btn-danger">Reset All Game PINs</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>