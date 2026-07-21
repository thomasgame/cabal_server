<head>
  <meta charset="UTF-8">
  <title>Dashboard - Global Cabal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@3/dist/fp.min.js"></script>
  <style>
    body {
      background: linear-gradient(to bottom, #000, #121212);
    }

/* Make the chat box floating at the bottom right of the screen */
.chat-container {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000; /* Ensure it stays on top */
}

/* The actual chat box */
.chat-box {
  width: 300px;
  height: 400px;
  background-color: rgba(50, 50, 50, 0.8); /* Darker and transparent background */
  border-radius: 10px;
  box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.4); /* Slightly stronger shadow for emphasis */
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: height 0.3s ease; /* Smooth transition when minimizing */
}

/* Chat header (smaller header with minimize button) */
.chat-header {
  background-color: rgba(0, 123, 255, 0.8); /* Semi-transparent blue header */
  color: white;
  padding: 8px;
  font-weight: bold;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

/* Minimize button */
.minimize-btn {
  background: none;
  border: none;
  color: white;
  font-size: 20px;
  cursor: pointer;
  padding: 0;
  margin-left: 10px;
}

/* Chat body (messages area) */
#chat-box {
  flex-grow: 1;
  padding: 10px;
  overflow-y: auto;
  background-color: rgba(0, 0, 0, 0.2); /* Slightly darkened transparent body */
}

.message {
  margin-bottom: 10px;
  color: white; /* White text for better visibility */
}

/* Message input area */
#chat-form {
  padding: 10px;
  background-color: rgba(0, 0, 0, 0.3); /* Slight transparency for the input */
  border-top: 1px solid rgba(255, 255, 255, 0.2); /* Light transparent border */
  display: flex;
  align-items: center;
}

#chat-message {
  padding: 10px;
  border: none;
  border-radius: 5px;
  width: 100%;
  background-color: rgba(0, 0, 0, 0.5); /* Dark background */
  color: white;
  font-size: 14px;
  outline: none;
}

#chat-message:focus {
  background-color: rgba(0, 0, 0, 0.7);
}

/* Send button */
button[type="submit"] {
  background-color: #fbbf24;
  hover:bg-yellow-600;
  text-black;
  padding: 8px 15px;
  border-radius: 5px;
  margin-left: 10px;
  font-weight: bold;
  transition: background-color 0.3s ease;
}

button[type="submit"]:hover {
  background-color: #facc15;
}

  </style>
</head>

<body class="text-white font-sans overflow-x-hidden">

<!-- Navigation Bar -->
<header class="fixed top-0 left-0 w-full bg-gradient-to-r from-black to-gray-900 backdrop-blur-sm shadow-lg z-50">
  <div class="max-w-7xl mx-auto flex justify-between items-center p-4">
    <div class="text-2xl font-extrabold tracking-wider text-yellow-400">Global Cabal</div>
       <nav class="hidden md:flex space-x-6 text-sm font-medium items-center">
      <a href="/php/dashboard.php#profile" class="hover:text-yellow-400">Profile</a>
       <a href="/php/dashboard.php#voting" class="hover:text-yellow-400">Voting</a>
      <a href="/php/dashboard.php#otp" class="hover:text-yellow-400">OTP</a>
      <a href="/php/dashboard.php#characters" class="hover:text-yellow-400">Characters</a>
      <a href="/shop/webshop.php" class="hover:text-yellow-400">Shop</a>
 <a href="download.php" class="hover:text-yellow-400">Downloads</a>
      <?php if ($isAdmin): ?>
      <a href="Admin/admin_panel.php" class="hover:text-yellow-400 font-bold">Admin</a>
      <?php endif; ?>
      <button onclick="toggleSettings()" class="hover:text-yellow-400">Settings</button>
      <a href="/logout.php" class="hover:text-red-400">Logout</a>
    </nav>
    <div class="md:hidden">
      <button id="menu-btn" class="focus:outline-none">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </div>
  </div>
  <!-- Mobile Menu -->
  <div id="menu" class="hidden md:hidden bg-gray-900 px-6 py-4">
    <nav class="flex flex-col space-y-3 text-sm">
      <a href="#profile" class="hover:text-yellow-400">Profile</a>
       <a href="#voting" class="hover:text-yellow-400">Voting</a>
      <a href="#otp" class="hover:text-yellow-400">OTP</a>
      <a href="#characters" class="hover:text-yellow-400">Characters</a>
      <a href="/shop/webshop.php" class="hover:text-yellow-400">Shop</a>
<a href="download.php" class="hover:text-yellow-400">Download</a>
      <?php if ($isAdmin): ?>
      <a href="admin_panel.php" class="hover:text-yellow-400 font-bold">Admin</a>
      <?php endif; ?>
      <button onclick="toggleSettings()" class="hover:text-yellow-400">Settings</button>
      <a href="/logout.php" class="hover:text-red-400">Logout</a>
    </nav>
  </div>


<!-- Settings Sidebar -->
<aside id="settingsSidebar" class="fixed top-0 right-0 h-full w-64 bg-gray-900 text-white transform translate-x-full transition-transform duration-300 z-40 p-6">
  <h2 class="text-xl font-bold mb-4">Account Settings</h2>
  <ul class="space-y-3 text-sm">
    <li><a href="update_profile.php" class="hover:text-yellow-400">Edit Profile</a></li>
    <li><a href="change_pass.php" class="hover:text-yellow-400">Change Password</a></li>
    <li><a href="manage-security.php" class="hover:text-yellow-400">Manage Security</a></li>
    <li><a href="delete-account.php" class="hover:text-red-400">Delete Account</a></li>
  </ul>
  <button onclick="toggleSettings()" class="absolute top-4 right-4 text-gray-400 hover:text-white text-xl">&times;</button>
</aside>

</header>
