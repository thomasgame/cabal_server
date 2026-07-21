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
      <a href="/shop/webshop.html" class="hover:text-yellow-400">Shop</a>
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
      <a href="/shop/webshop.html" class="hover:text-yellow-400">Shop</a>
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
    <li><a href="profile.php" class="hover:text-yellow-400">Edit Profile</a></li>
    <li><a href="change-password.php" class="hover:text-yellow-400">Change Password</a></li>
    <li><a href="manage-security.php" class="hover:text-yellow-400">Manage Security</a></li>
    <li><a href="delete-account.php" class="hover:text-red-400">Delete Account</a></li>
  </ul>
  <button onclick="toggleSettings()" class="absolute top-4 right-4 text-gray-400 hover:text-white text-xl">&times;</button>
</aside>

</header>
