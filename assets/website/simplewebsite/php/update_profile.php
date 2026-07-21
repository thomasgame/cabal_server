<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Database connection
require_once(__DIR__ . '/../php/db.php');

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit();
}

$username = $_SESSION['user'];
$userNum = $_SESSION['usernum'];

// Admin check (optional)
$isAdmin = in_array(strtolower($username), array_map('strtolower', $adminUsernames), true);

// Initialize variables for the form
$email = $phone = $firstName = $lastName = $birthday = $gender = '';

// Fetch current user profile data from the database
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "EXEC dbo.get_cabal_auth_table :UserNum";
    $stmt =  $conn->prepare($query);
    $stmt->bindParam(':UserNum', $userNum, PDO::PARAM_INT);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        die("User not found.");
    }

    // Populate the form with existing user data
    $email = $user['Email'];
    $phone = $user['Phone'];
    $FirstName = $user['FirstName'];
    $lastName = $user['LastName'];
    $birthday = $user['Birthday'];
    $gender = $user['Gender'];
}

// Update user profile on POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect updated values from the form
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $birthday = $_POST['birthday'];
    $gender = $_POST['gender'];

    // SQL query to update user profile
    $updateQuery = "EXEC dbo.update_cabal_auth_table 
                    @UserNum = :UserNum, 
                    @Email = :Email, 
                    @Phone = :Phone, 
                    @First_Name = :First_Name, 
                    @Last_Name = :Last_Name, 
                    @Birthday = :Birthday, 
                    @Gender = :Gender";

    // Prepare and execute the query
    $stmt =  $conn->prepare($updateQuery);
    $stmt->bindParam(':UserNum', $userNum, PDO::PARAM_INT);
    $stmt->bindParam(':Email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':Phone', $phone, PDO::PARAM_STR);
    $stmt->bindParam(':First_Name', $firstName, PDO::PARAM_STR);
    $stmt->bindParam(':Last_Name', $lastName, PDO::PARAM_STR);
    $stmt->bindParam(':Birthday', $birthday, PDO::PARAM_STR);
    $stmt->bindParam(':Gender', $gender, PDO::PARAM_STR);
    $stmt->execute();

    // Redirect to the profile page after update
    header("Location:dashboard.php");
    exit();
}
?>


<?php include 'include/header.php'; ?>

<body class="text-white font-sans overflow-x-hidden">
<!-- Main -->
<main class="pt-24 px-4 max-w-5xl mx-auto">

    <!-- Profile Header -->
    <section class="bg-gray-900 p-6 rounded-lg shadow-lg text-center mb-10">
        <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></h1>
        <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($email); ?></p>
        <div class="flex justify-center gap-8 mt-4">
            <div>
                <p class="text-yellow-400 text-lg font-semibold"><?php echo htmlspecialchars($phone); ?></p>
                <p class="text-gray-400 text-sm">Phone</p>
            </div>
            <a href="topup.php" class="bg-yellow-400 text-black px-4 py-2 rounded-lg hover:bg-yellow-500 transition">Top-Up</a>
        </div>
    </section>

    <!-- Edit Profile -->
    <section class="bg-gray-800 p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold mb-6">Edit Profile</h2>
        <form action="update_profile.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-gray-300">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="w-full bg-gray-700 text-white p-2 rounded">
            </div>
            <div>
                <label class="block text-gray-300">Phone Number</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" class="w-full bg-gray-700 text-white p-2 rounded">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-300">First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($firstName); ?>" class="w-full bg-gray-700 text-white p-2 rounded">
                </div>
                <div>
                    <label class="block text-gray-300">Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($lastName); ?>" class="w-full bg-gray-700 text-white p-2 rounded">
                </div>
            </div>
            <div>
                <label class="block text-gray-300">Birthday</label>
                <input type="date" name="birthday" value="<?php echo htmlspecialchars($birthday); ?>" class="w-full bg-gray-700 text-white p-2 rounded">
            </div>
            <div>
                <label class="block text-gray-300">Gender</label>
                <select name="gender" class="w-full bg-gray-700 text-white p-2 rounded">
                    <option value="" <?php echo !$gender ? 'selected' : ''; ?>>Select Gender</option>
                    <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-yellow-400 text-black font-semibold py-2 rounded hover:bg-yellow-500 transition">Save Changes</button>
        </form>
    </section>

</main>

<script>
const menuBtn = document.getElementById('menu-btn');
const menu = document.getElementById('menu');

// if menu exists (on mobile)
if(menuBtn && menu) {
    menuBtn.addEventListener('click', () => {
        menu.classList.toggle('hidden');
    });
}

// Sidebar toggle
function toggleSettings() {
    const sidebar = document.getElementById('settingsSidebar');
    sidebar.classList.toggle('translate-x-full');
}
</script>

</body>
</html>
