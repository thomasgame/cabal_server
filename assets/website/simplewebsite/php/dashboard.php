<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once(__DIR__ . '/../php/db.php');

if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit();
}

$username = $_SESSION['user'];
$usernum = $_SESSION['usernum'];

$isAdmin = in_array(strtolower($username), array_map('strtolower', $adminUsernames), true);

include('generate_aid.php');
$aid = generateAid($usernum);

$balance = 0;
$forcegemHave = 0;
$playtimeDisplay = 0;
$redeemMessage = '';
$redeemError = '';
$userInviteCode = '';
$inviteURL = '';

try {
    // Get character list
    $stmt = $connServer->prepare("EXEC get_cabal_character_list ?");
    $stmt->execute([$usernum]);
    $characters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get cash balance
    $stmt = $connCash->prepare("EXEC up_GetUserCashInfo :usernum");
    $stmt->bindParam(':usernum', $usernum, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && isset($result['CashTotal'])) {
        $balance = $result['CashTotal'];
    }

    // Get forcegem
    $stmt = $connServer->prepare("EXEC dbo.cabal_tool_forcegem_get :usernum");
    $stmt->bindParam(':usernum', $usernum, PDO::PARAM_INT);
    $stmt->execute();
    $forcegemResult = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($forcegemResult && isset($forcegemResult['ForcegemHave'])) {
        $forcegemHave = $forcegemResult['ForcegemHave'];
    }

    // Fetch invite code
    try {
        $stmt = $conn->prepare("EXEC GetUserInviteCode :usernum");
        $stmt->bindParam(':usernum', $usernum, PDO::PARAM_INT);
        $stmt->execute();
        $inviteData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($inviteData && isset($inviteData['InviteCode']) && !empty($inviteData['InviteCode'])) {
            // Sanitize the invite code and generate the URL
            $userInviteCode = htmlspecialchars($inviteData['InviteCode']);
            $publicUrl = rtrim(getenv('WEBSITE_PUBLIC_URL') ?: '', '/');
            $inviteURL = $publicUrl . "/registration/registration.html?ref=" . urlencode($userInviteCode);
        } else {
            // Handle case where invite code doesn't exist or is empty
            $inviteURL = ''; // Or provide a default message
        }
    } catch (PDOException $e) {
        error_log("Error fetching invite code: " . $e->getMessage());
        $userInviteCode = 'Error fetching code'; // You can set an error message if needed
    }
    
} catch (PDOException $e) {
    error_log("Error loading user data: " . $e->getMessage());
    $userInviteCode = 'Error fetching code';
}


// Count all invite redemptions by inviter (no JOIN)

try {
    $stmt = $conn->prepare("EXEC sp_GetInviteCountByUser :usernum");
    $stmt->bindParam(':usernum', $usernum, PDO::PARAM_INT);
    $stmt->execute();
    $inviteStats = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalInvites = (int)($inviteStats['InviteCount'] ?? 0);
} catch (PDOException $e) {
    error_log("Stored procedure error: " . $e->getMessage());
    $totalInvites = 0;
}


// Rank progression logic
$inviteRanks = [
    ['name' => 'Bronze', 'target' => 10, 'reward' => '3000 Ecoins'],
    ['name' => 'Silver', 'target' => 25, 'reward' => '6050 Ecoins'],
    ['name' => 'Gold', 'target' => 50, 'reward' => '9000 Ecoins'],
    ['name' => 'Platinum', 'target' => 100, 'reward' => '10000 Ecoins']
];

$currentRank = ['name' => 'None', 'target' => 0, 'reward' => ''];
$nextRank = null;

foreach ($inviteRanks as $index => $rank) {
    if ($totalInvites >= $rank['target']) {
        $currentRank = $rank;
        $nextRank = $inviteRanks[$index + 1] ?? null;
    } elseif (!$nextRank) {
        $nextRank = $rank;
    }
}

$progressPercent = $nextRank ? min(100, ($totalInvites / $nextRank['target']) * 100) : 100;


try {
    $stmt = $conn->prepare("EXEC usp_ClaimInviteRankReward @UserNum = :usernum, @CurrentRank = :rank, @Reward = :reward");
    $stmt->execute([
        ':usernum' => $usernum,
        ':rank'    => $currentRank['name'],
        ':reward'  => $currentRank['reward'],
    ]);

    // Optional feedback message
    $redeemMessage = "?? You've reached <strong>{$currentRank['name']}</strong> rank and received <strong>{$currentRank['reward']}</strong>!";

} catch (PDOException $e) {
    error_log("Rank reward procedure error: " . $e->getMessage());
}
  // Map rank names to badge image paths
  $rankImages = [
    'Bronze' => 'badge/badge-bronze.png',
    'Silver' => 'badge/badge-silver.png',
    'Gold' => 'badge/badge-gold.png',
    'Platinum' => 'badge/badge-platinum.png',
    'Diamond' => 'badge/badge-diamond.png',
    'Legend' => 'badge/badge-legend.png',
  ];

  $rankName = $currentRank['name'] ?? 'Bronze';
  $rankImage = $rankImages[$rankName] ?? 'badge/badge-default.png';

  $isGlowRank = in_array($rankName, ['Diamond', 'Legend']);

// Fetch user profile from database
$query = "EXEC dbo.get_cabal_auth_table :UserNum";
$stmt = $conn->prepare($query);
$stmt->bindParam(':UserNum', $usernum, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Assign data to variables
$email = $user['Email'] ?? '';
$phone = $user['Phone'] ?? '';
$firstName = $user['FirstName'] ?? '';
$lastName = $user['LastName'] ?? '';
$birthday = $user['Birthday'] ?? '';
$gender = $user['Gender'] ?? '';

?>

<?php include 'include/header.php'; ?>

<!-- Main Content -->
<main class="pt-24 px-6 max-w-7xl mx-auto">

<!-- Profile Section -->
<section id="profile" class="bg-gradient-to-br from-gray-800 to-gray-900 text-white p-10 rounded-2xl shadow-2xl mb-10 w-full max-w-[90rem] mx-auto">

  <!-- Profile Overview -->
  <div class="flex flex-col items-center text-center mb-10 space-y-4">
    <!-- Dynamic Rank Badge & Title -->
    <div class="relative group">
      <img src="<?= $rankImage ?>" alt="<?= htmlspecialchars($rankName) ?> Badge"
           class="w-32 h-32 rounded-full shadow-xl border-4 border-yellow-400 ring-4 ring-yellow-300 <?= $isGlowRank ? 'animate-glow' : 'animate-pulse' ?>">
      <span class="absolute -bottom-6 left-1/2 transform -translate-x-1/2 text-yellow-300 font-black text-2xl bg-gray-800 px-4 py-1 rounded-full shadow-md uppercase tracking-wide">
        <?= strtoupper($rankName) ?>
      </span>
    </div>

    <h2 class="text-5xl font-extrabold text-yellow-200 drop-shadow-md"><?= htmlspecialchars($username); ?></h2>
    <p class="text-sm text-gray-400">AID: <?= htmlspecialchars($aid); ?></p>

    <a href="update_profile.php" class="bg-yellow-500 hover:bg-yellow-600 text-black px-6 py-3 rounded-full shadow-lg transition transform hover:scale-105">
      Profile Settings
    </a>
  </div>

  <!-- Profile Details -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-lg text-gray-300 max-w-4xl mx-auto">
    <div><span class="font-semibold text-yellow-300">Email:</span> <?= htmlspecialchars($email) ?></div>
    <div><span class="font-semibold text-yellow-300">Phone:</span> <?= htmlspecialchars($phone) ?></div>
    <div><span class="font-semibold text-yellow-300">First Name:</span> <?= htmlspecialchars($firstName) ?></div>
    <div><span class="font-semibold text-yellow-300">Last Name:</span> <?= htmlspecialchars($lastName) ?></div>
    <div><span class="font-semibold text-yellow-300">Birthday:</span> <?= htmlspecialchars($birthday) ?></div>
   <div> <span class="font-semibold text-yellow-300">Gender:</span> <?= htmlspecialchars($gender) ?></div>
 <div></div>
  </div>

<!-- Rank Progress + Reward -->
  <div class="mb-10 px-4 sm:px-8">
    <div class="flex items-center justify-between mb-2">
      <p class="text-gray-300 text-lg">Progress to <span class="font-semibold text-yellow-400"><?= $nextRank['name'] ?? 'Max Rank' ?></span></p>
      <?php if ($nextRank): ?>
        <p class="text-green-400 text-sm bg-gray-700 px-3 py-1 rounded-full shadow-md cursor-help" title="You will receive this reward after <?= $nextRank['target'] ?> invites">
          ?? <?= $nextRank['reward'] ?>
        </p>
      <?php else: ?>
        <p class="text-pink-400 text-sm bg-gray-700 px-3 py-1 rounded-full shadow-md animate-bounce">You have reached the Max Rank!</p>
      <?php endif; ?>
    </div>
    <div class="w-full bg-gray-700 h-5 rounded-full overflow-hidden shadow-inner">
      <div class="bg-yellow-400 h-5 rounded-full transition-all duration-500 ease-in-out" style="width: <?= round($progressPercent) ?>%"></div>
    </div>
    <p class="text-right text-xs text-gray-400 mt-1"><?= $totalInvites ?>/<?= $nextRank['target'] ?? 'MAX' ?> Invites</p>
  </div>

<!-- Invite Code Area -->
<div class="bg-gray-800 p-4 rounded-xl border border-gray-700 mb-8 shadow-md text-sm">
  <h3 class="text-lg font-bold text-yellow-400 mb-1">Invite Code</h3>
  <p class="text-xs text-gray-300 mb-3">Share this link with friends to earn rewards!</p>

  <div class="bg-gray-900 p-3 rounded-lg border border-gray-700 mb-3">
    <p class="text-green-400 font-mono text-sm mb-1">Code: <span class="font-bold"><?= htmlspecialchars($userInviteCode) ?></span></p>
    <p id="invite-link" class="text-blue-400 font-mono text-xs break-all">
      <?= htmlspecialchars("http://global-cabal.servegame.com/registration/registration.html?ref=" . $userInviteCode); ?>
    </p>
  </div>

  <button id="copyButton" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-1.5 text-xs rounded-full shadow-sm transition hover:scale-105">
    Copy Invite
  </button>
  <p id="copy-feedback" class="text-green-400 text-xs mt-2 hidden">? Link copied!</p>

  <script>
    // Function to copy the invite link to clipboard
    function copyInviteLink() {
      const link = document.getElementById("invite-link").textContent; // Get the link text content
      if (navigator.clipboard) {
        navigator.clipboard.writeText(link)
          .then(() => {
            // Show success message if copy is successful
            const feedback = document.getElementById("copy-feedback");
            feedback.classList.remove("hidden"); // Show feedback message
            setTimeout(() => feedback.classList.add("hidden"), 2500); // Hide feedback after 2.5 seconds
          })
          .catch(err => {
            // Handle clipboard write error
            console.error("Failed to copy text: ", err);
            alert("Copy failed! Please try again.");
          });
      } else {
        // Fallback method for browsers that don't support Clipboard API
        const textArea = document.createElement('textarea');
        textArea.value = link;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        // Show success message
        const feedback = document.getElementById("copy-feedback");
        feedback.classList.remove("hidden");
        setTimeout(() => feedback.classList.add("hidden"), 2500);
      }
    }

    // Attach event listener to the copy button
    document.getElementById('copyButton').addEventListener('click', copyInviteLink);
  </script>
</div>


  <!-- Account Stats -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-center px-4">
    <div class="bg-gray-800 p-5 rounded-xl shadow-md text-green-400">
      <p class="text-sm">Ecoins</p>
      <p class="text-2xl font-bold"><?= number_format($balance); ?></p>
    </div>
    <div class="bg-gray-800 p-5 rounded-xl shadow-md text-green-400">
      <p class="text-sm">ForceGems</p>
      <p class="text-2xl font-bold"><?= number_format($forcegemHave); ?></p>
    </div>
    <div class="bg-gray-800 p-5 rounded-xl shadow-md text-green-400">
      <p class="text-sm">Total Invites</p>
      <p class="text-2xl font-bold"><?= $totalInvites ?></p>
    </div>
  </div>

</section>

<!-- Extra CSS for Glow Animation -->
<style>
  @keyframes glow {
    0% { box-shadow: 0 0 10px rgba(255,255,255,0.2); }
    50% { box-shadow: 0 0 25px rgba(255,255,255,0.6); }
    100% { box-shadow: 0 0 10px rgba(255,255,255,0.2); }
  }

  .animate-glow {
    animation: glow 2s infinite ease-in-out;
  }
</style>

  <!-- Voting & OTP Section -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <section id="voting" class="bg-gray-900 text-white shadow-lg rounded-lg p-6">
      <!-- Voting content here -->
 <div id="announcement-bar" class="w-full bg-yellow-400 text-black text-center py-2 px-4 font-semibold shadow-md mb-4 rounded">
        Vote now and earn <strong>150 Ecoins</strong> per vote!
      </div>
      <form action="/php/vote.php" method="POST" class="space-y-4">
        <input type="hidden" name="hwid" id="hwid">
        <div class="grid grid-cols-1 gap-4">
          <!-- GTOP100 -->
          <button type="submit" name="site" value="gtop100"
            class="w-full bg-gray-800 border border-blue-500 text-white rounded hover:bg-blue-900 p-2 flex items-center justify-center transition transform hover:scale-105">
            <img src="https://gtop100.com/assets/images/votebutton.jpg" alt="Vote via GTOP100" class="max-h-12">
          </button>

          <!-- XtremeTop100 -->
          <button type="submit" name="site" value="xtremetop100"
            class="w-full bg-gray-800 border border-green-500 text-white rounded hover:bg-green-900 p-2 flex items-center justify-center transition transform hover:scale-105">
            <img src="https://www.xtremetop100.com/votenew.jpg" alt="Vote via XtremeTop100" class="max-h-12">
          </button>
        </div>
      </form>

    </section>
    <section id="otp" class="bg-gray-900 text-white shadow-lg rounded-lg p-6">
      <!-- OTP content here -->
<div class="bg-gray-900 text-white shadow-lg rounded-lg p-6 text-center">
      <h2 class="text-xl font-bold mb-2">Security Settings</h2>
      <p class="text-sm mb-2">A new OTP will be generated every <strong>10 seconds</strong>.</p>
      <div id="countdown" class="text-sm mb-4 text-yellow-400">Next OTP in: 10s</div>
      <div id="otpDisplay" class="mt-2 text-green-400 font-bold text-xl">Fetching OTP...</div>
      <script>
        let countdown = 10;
        const countdownEl = document.getElementById('countdown');
        const otpDisplay = document.getElementById('otpDisplay');

        function fetchOtp() {
          fetch('get_otp.php')
            .then(response => response.text())
            .then(data => {
              otpDisplay.innerHTML = "Your OTP: " + data;
              countdown = 10; // reset timer
            })
            .catch(() => {
              otpDisplay.innerHTML = "Error fetching OTP.";
            });
        }

        // Initial fetch
        fetchOtp();

        // Countdown + auto-fetch logic
        setInterval(() => {
          countdown--;
          countdownEl.innerText = `Next OTP in: ${countdown}s`;

          if (countdown <= 0) {
            fetchOtp();
          }
        }, 1000);
      </script>
    </div>
    </section>
  </div>

  <!-- Characters Section -->
  <section id="characters" class="bg-gray-900 text-white p-6 rounded-lg shadow-lg">
    <!-- Characters table here -->
 <table class="w-full text-sm bg-gray-800 rounded-lg overflow-hidden">
    <thead class="bg-gray-700 text-yellow-300">
      <tr>
        <th class="p-2 text-left">Character Name</th>
        <th class="p-2 text-left">Level</th>
        <th class="p-2 text-left">Nation</th>
        <th class="p-2 text-left">Rank</th>
        <th class="p-2 text-left">Reputation</th>
        <th class="p-2 text-left">Created</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($characters) > 0): ?>
        <?php foreach ($characters as $char): ?>
          <tr class="border-t border-gray-700 hover:bg-gray-700">
            <td class="p-2">
              <a href="edit_character2.php?name=<?= urlencode($char['Name']) ?>" class="text-yellow-400 hover:underline">
                <?= htmlspecialchars($char['Name']) ?>
              </a>
            </td>
            <td class="p-2"><?= htmlspecialchars($char['LEV']) ?></td>
            <td class="p-2"><?= htmlspecialchars($char['NATION']) ?></td>
            <td class="p-2"><?= htmlspecialchars($char['RANK']) ?></td>
            <td class="p-2"><?= htmlspecialchars($char['Reputation']) ?></td>
            <td class="p-2"><?= htmlspecialchars($char['CreateDate']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="6" class="p-4 text-center text-gray-400">No characters found for this user.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  </section>

<!-- Group Chat Section -->
<div class="chat-container">
  <div class="chat-box">
    <!-- Chat Header -->
    <div class="chat-header">
      <h4 class="text-lg font-bold text-yellow-400">Global Cabal Chat</h4>
      <button class="minimize-btn" onclick="toggleChat()">&#9660;</button> <!-- Minimize button -->
    </div>

    <!-- Chat Box -->
    <div id="chat-box" class="bg-gray-800 p-4 h-64 overflow-y-scroll rounded mb-4 text-sm space-y-2">
      <!-- Messages will be loaded here -->
    </div>

    <!-- Message Form -->
    <form id="chat-form" action="/php/send_message.php" method="POST" class="flex space-x-2">
      <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
      <input type="text" name="message" id="chat-message" placeholder="Type your message..."
             class="flex-1 px-4 py-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-500"
             required>
      <button type="submit"
              class="bg-yellow-500 hover:bg-yellow-600 text-black px-4 py-2 rounded font-bold transition">
        Send
      </button>
    </form>
  </div>
</div>

<!-- Group Chat Script -->
<script>
function loadChat() {
  fetch('/php/get_messages.php')
    .then(response => response.text())
    .then(data => {
      const chatBox = document.getElementById('chat-box');
      chatBox.innerHTML = data;
      chatBox.scrollTop = chatBox.scrollHeight;
    });
}

document.getElementById('chat-form').addEventListener('submit', function(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);

  fetch(form.action, {
    method: 'POST',
    body: formData
  }).then(() => {
    form.reset();
    loadChat();
  });
});

setInterval(loadChat, 5000); // Refresh every 5 seconds
loadChat(); // Initial load


</script>




</main>

<?php include 'include/footer.php'; ?>


<script>
  const menuBtn = document.getElementById('menu-btn');
  const menu = document.getElementById('menu');
  const sidebar = document.getElementById('settingsSidebar');

  menuBtn.addEventListener('click', () => {
    menu.classList.toggle('hidden');
  });

  function toggleSettings() {
    sidebar.classList.toggle('translate-x-full');
  }

const minimizeBtn = document.querySelector('.minimize-btn');
const chatBox = document.querySelector('.chat-box');
const chatBody = document.querySelector('.chat-body');
const chatInput = document.querySelector('.chat-input');

minimizeBtn.addEventListener('click', () => {
  // Toggle the visibility of the chat body
  if (chatBody.style.display === 'none') {
    chatBody.style.display = 'block'; // Show the chat body
    chatBox.style.height = '400px'; // Restore original height
  } else {
    chatBody.style.display = 'none'; // Hide the chat body
    chatBox.style.height = '50px'; // Set to a small height when minimized
  }
});

chatInput.addEventListener('keypress', (e) => {
  if (e.key === 'Enter' && chatInput.value.trim() !== '') {
    const newMessage = document.createElement('div');
    newMessage.classList.add('message');
    newMessage.textContent = chatInput.value;
    chatBody.appendChild(newMessage);
    chatBody.scrollTop = chatBody.scrollHeight; // Auto scroll to the latest message
    chatInput.value = ''; // Clear the input field
  }
});

function toggleChat() {
  var chatBox = document.querySelector('.chat-box');
  var minimizeBtn = document.querySelector('.minimize-btn');

  // Toggle visibility of chat body (hide/show)
  if (chatBox.style.height === "40px") { // Minimized state
    chatBox.style.height = "400px"; // Original height
    minimizeBtn.innerHTML = "&#9660;"; // Down arrow when open
  } else {
    chatBox.style.height = "40px"; // Minimized state
    minimizeBtn.innerHTML = "&#9650;"; // Up arrow when minimized
  }
}
</script>
</body>
</html>
