<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once(__DIR__ . '/../php/db.php');

// Function to convert Google Drive link to direct download link
function convertGoogleDriveLink($url) {
    if (preg_match('#https://drive\.google\.com/file/d/([a-zA-Z0-9_-]+)#', $url, $matches)) {
        return "https://drive.google.com/uc?export=download&id=" . $matches[1];
    }
    return $url;  // Return the original URL if not a Google Drive link
}

// Handle file upload or link submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate input fields
    if (isset($_POST['filename'], $_POST['filepath']) && !empty($_POST['filename']) && !empty($_POST['filepath'])) {
        $filename = trim($_POST['filename']);
        $filepath = trim($_POST['filepath']);
        $size = isset($_POST['size']) ? (int) $_POST['size'] : 0; // Size is optional, default to 0

        // Convert Google Drive link if necessary
        $convertedUrl = convertGoogleDriveLink($filepath);

        try {
            // Debug the values
            echo "Filename: " . $filename . "<br>";
            echo "Filepath: " . $convertedUrl . "<br>";
            echo "Size: " . $size . "<br>";

            // Insert the file details into the database
            $stmt = $conn->prepare("INSERT INTO downloads (filename, filepath, size) VALUES (?, ?, ?)");
            $stmt->execute([$filename, $convertedUrl, $size]);

            // Debug the result
            if ($stmt->rowCount() > 0) {
                echo "<p>File uploaded successfully!</p>";
            } else {
                echo "<p>No rows affected.</p>";
            }

            // Redirect after successful insertion
            header("Location: download.php");
            exit();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage(); // Display error message if something goes wrong
        }
    } else {
        echo "<p class='text-red-500 text-center'>Please provide both file name and file path.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Upload Files</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#1c2230] text-white min-h-screen p-6">
    <div class="max-w-4xl mx-auto bg-[#2b3140] p-8 rounded-xl shadow-lg">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold">Upload Files</h2>
        </div>

        <!-- File upload form -->
        <form method="POST">
            <div class="space-y-4">
                <input type="text" name="filename" placeholder="File Name" required class="w-full px-4 py-3 rounded-md bg-[#2b3140] focus:outline-none focus:ring-2 focus:ring-yellow-400">
                <input type="text" name="filepath" placeholder="Google Drive Link" required class="w-full px-4 py-3 rounded-md bg-[#2b3140] focus:outline-none focus:ring-2 focus:ring-yellow-400">
                <input type="number" name="size" placeholder="Size (optional)" class="w-full px-4 py-3 rounded-md bg-[#2b3140] focus:outline-none focus:ring-2 focus:ring-yellow-400">
                <button type="submit" class="w-full py-3 bg-yellow-400 hover:bg-yellow-500 rounded-md font-bold text-gray-900 transition">Upload</button>
            </div>
        </form>

        <!-- Footer -->
        <p class="text-center text-xs text-gray-500 mt-6">© 2025 Global Cabal. All rights reserved.</p>
    </div>
</body>
</html>
