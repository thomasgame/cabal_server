<?php
// /api/logger.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? preg_replace("/[^a-zA-Z0-9_\-]/", "", $_POST['username']) : 'unknown';
    $hwid = isset($_POST['hwid']) ? preg_replace("/[^a-zA-Z0-9]/", "", $_POST['hwid']) : 'unknown';
    
    $logEntry = date("Y-m-d H:i:s") . " | User: " . $username . " | HWID: " . $hwid . PHP_EOL;
    
    // Ensure the directory exists
    if (!file_exists('../logs')) {
        mkdir('../logs', 0777, true);
    }
    
    file_put_contents('../logs/user_logs.txt', $logEntry, FILE_APPEND);
    echo "OK";
}
?>
