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

$adminUsernames = ['admin', 'aerox009x', 'Kuyatel'];
$isAdmin = in_array(strtolower($username), array_map('strtolower', $adminUsernames));

// Function to convert Google Drive link to direct download
function convertGoogleDriveLink($url) {
    if (preg_match('#https://drive\.google\.com/file/d/([a-zA-Z0-9_-]+)#', $url, $matches)) {
        return "https://drive.google.com/uc?export=download&id=" . $matches[1];
    }
    return $url;
}

// Handle external download redirect
if (isset($_GET['download'])) {
    $id = (int) $_GET['download'];

    $stmt = $conn->prepare("SELECT * FROM downloads WHERE id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file && filter_var($file['filepath'], FILTER_VALIDATE_URL)) {
        // Convert Google Drive link if applicable
        $url = convertGoogleDriveLink($file['filepath']);
        
        header("Location: " . $url);
        exit();
    } else {
        die("Invalid or missing download link.");
    }
}
?>

<?php include 'include/header.php'; ?>

<!-- Main Content -->
<main class="pt-24 px-6 max-w-7xl mx-auto">


<body class="bg-[#1c2230] text-white min-h-screen p-6">
    <div class="max-w-4xl mx-auto bg-[#2b3140] p-8 rounded-xl shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Download Center</h2>
            <a href="logout.php" class="text-sm text-yellow-400 hover:underline">Logout</a>
        </div>

        <table class="w-full text-left table-auto">
            <thead class="bg-blue-800 text-white">
                <tr>
                    <th class="px-6 py-3 text-lg font-semibold">File</th>
                    <th class="px-6 py-3 text-lg font-semibold">Size</th>
                    <th class="px-6 py-3 text-lg font-semibold">Uploaded</th>
                    <th class="px-6 py-3 text-lg font-semibold">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conn->query("SELECT * FROM downloads ORDER BY uploaded_at DESC");
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $file):
                ?>
                <tr class="border-b border-gray-700 hover:bg-[#3b4252] transition-colors">
                    <td class="px-6 py-4"><?= htmlspecialchars($file['filename']) ?></td>
                    <td class="px-6 py-4"><?= number_format($file['size'] / 1024, 2) ?> KB</td>
                    <td class="px-6 py-4"><?= htmlspecialchars($file['uploaded_at']) ?></td>
                    <td class="px-6 py-4">
                        <a href="?download=<?= (int)$file['id'] ?>" class="bg-yellow-400 text-black px-4 py-2 rounded hover:bg-yellow-500 transition-colors">Download</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="text-center text-xs text-gray-500 mt-6">© 2025 Global Cabal. All rights reserved.</p>
    </div>
</body>
</main>
</html>
