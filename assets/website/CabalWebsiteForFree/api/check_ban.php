<?php
// Enable error reporting to see the actual error message in the browser
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$hwid = $_GET['hwid'] ?? '';
$filename = "bans.txt";

// Check if file exists first to prevent 500 error
if (file_exists($filename)) {
    $banned_hwids = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (in_array($hwid, $banned_hwids)) {
        echo "BANNED";
    } else {
        echo "CLEAN";
    }
} else {
    // If file is missing, create it or just return CLEAN
    echo "CLEAN (Warning: bans.txt missing)";
}
?>