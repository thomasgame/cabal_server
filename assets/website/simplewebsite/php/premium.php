<?php
session_start();
require_once(__DIR__ . '/../php/db.php');

if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit();
}

$username = $_SESSION['user'];
$usernum = $_SESSION['usernum'];
$serviceKind = 1; // example, adjust as needed

try {
    $stmt = $conn->prepare("SELECT ExpireDate FROM cabal_charge_auth WHERE UserNum = :usernum");
    $stmt->bindParam(':usernum', $usernum, PDO::PARAM_INT);
    $stmt->execute();

    $expireDate = $stmt->fetchColumn();
} catch (PDOException $e) {
    $expireDate = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Buy Premium Access</title>
  <style>
    body {
      background: #121212;
      color: #eee;
      font-family: Arial, sans-serif;
      max-width: 600px;
      margin: 2rem auto;
      padding: 1rem;
      border-radius: 8px;
      background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
    }
    label, select, button {
      display: block;
      width: 100%;
      margin-top: 1rem;
      font-size: 1.1rem;
    }
    select, button {
      padding: 0.6rem 0.8rem;
      border-radius: 6px;
      border: none;
      background-color: #333;
      color: #eee;
    }
    button:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
  </style>
</head>
<body>
  <h1>Buy Premium Access</h1>
  <p>Hello, <strong><?= htmlspecialchars($username) ?></strong>!</p>

  <?php if ($expireDate): ?>
    <p>Your current premium expires on: <strong><?= htmlspecialchars($expireDate) ?></strong></p>
  <?php else: ?>
    <p>You currently do not have an active premium subscription.</p>
  <?php endif; ?>

  <label for="premiumSelect">Select Duration:</label>
  <select id="premiumSelect" aria-label="Select premium duration">
    <option value="">-- Select a duration --</option>
    <option value="0,1,0">1 Hour</option>
    <option value="0,2,0">2 Hours</option>
    <option value="1,0,0">1 Day</option>
    <option value="7,0,0">7 Days</option>
    <option value="30,0,0">30 Days</option>
  </select>
  
  <button id="buyButton" disabled>Buy Now</button>
  
  <script>
    const userNum = <?= json_encode($usernum) ?>;
    const serviceKind = <?= json_encode($serviceKind) ?>;
    const currentExpireDateStr = <?= json_encode($expireDate) ?>;

    const select = document.getElementById('premiumSelect');
    const buyButton = document.getElementById('buyButton');

    const currentExpireDate = currentExpireDateStr ? new Date(currentExpireDateStr) : null;

    function getNow() {
      return new Date();
    }

    select.addEventListener('change', () => {
      buyButton.disabled = !select.value;
    });

    buyButton.addEventListener('click', async () => {
      if (!select.value) {
        alert('Please select a premium duration.');
        return;
      }

      const [day, hour, min] = select.value.split(',').map(Number);

      const now = getNow();
      const baseDate = currentExpireDate && currentExpireDate > now ? currentExpireDate : now;

      const newExpireDate = new Date(baseDate);
      newExpireDate.setDate(newExpireDate.getDate() + day);
      newExpireDate.setHours(newExpireDate.getHours() + hour);
      newExpireDate.setMinutes(newExpireDate.getMinutes() + min);

      // Format newExpireDate as YYYY-MM-DD HH:mm:ss
      function pad(num) { return num.toString().padStart(2, '0'); }
      const expireDateStr = newExpireDate.getFullYear() + '-' + 
                            pad(newExpireDate.getMonth()+1) + '-' + 
                            pad(newExpireDate.getDate()) + ' ' + 
                            pad(newExpireDate.getHours()) + ':' + 
                            pad(newExpireDate.getMinutes()) + ':' + 
                            pad(newExpireDate.getSeconds());

      try {
        const response = await fetch('api/buy_premium.php', {

          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            userNum,
            serviceKind,
            expireDate: expireDateStr
          })
        });

        const result = await response.json();

        if (result.success) {
          alert('Premium purchased successfully!\nExpires: ' + result.expireDate);
          select.value = '';
          buyButton.disabled = true;
          // Update currentExpireDate to new expiry so next purchase extends correctly
          if (currentExpireDate) {
            currentExpireDate.setTime(newExpireDate.getTime());
          } else {
            window.location.reload(); // fallback: reload page to update expiry shown
          }
        } else {
          alert('Purchase failed: ' + result.message);
        }
      } catch (error) {
        alert('Network or server error.');
        console.error(error);
      }
    });
  </script>
</body>
</html>
