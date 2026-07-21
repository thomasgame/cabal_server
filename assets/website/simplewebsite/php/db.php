<?php
$serverName = (getenv('MSSQL_HOST') ?: 'database') . ',' . (getenv('MSSQL_PORT') ?: '1433');
$username = getenv('MSSQL_USER') ?: 'sa';
$password = getenv('MSSQL_PASSWORD') ?: '';

$accountDatabase = getenv('WEBSITE_DB_ACCOUNT') ?: 'Account';
$shopDatabase = getenv('WEBSITE_DB_SHOP') ?: 'CabalShop';
$cashDatabase = getenv('WEBSITE_DB_CASH') ?: 'CabalCash';
$gameDatabase = getenv('WEBSITE_DB_GAME') ?: 'Server01';

// Declare connection variables
$conn = null;       // Account DB (default)
$connShop = null;   // ItemShop DB
$connCash = null;   // Cash DB
$connServer = null; // Server01 DB

try {
    // Common PDO options with UTF-8 encoding
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ];

    // Connect to Account DB (default connection)
    $dsnOptions = 'Encrypt=yes;TrustServerCertificate=yes';
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$accountDatabase;$dsnOptions", $username, $password, $options);

    // Connect to CabalShop DB (for webshop)
    $connShop = new PDO("sqlsrv:Server=$serverName;Database=$shopDatabase;$dsnOptions", $username, $password, $options);

    // Connect to Cash DB (for Cash)
    $connCash = new PDO("sqlsrv:Server=$serverName;Database=$cashDatabase;$dsnOptions", $username, $password, $options);

    // Connect to Server01 DB (for character data, etc.)
    $connServer = new PDO("sqlsrv:Server=$serverName;Database=$gameDatabase;$dsnOptions", $username, $password, $options);

} catch (PDOException $e) {
    error_log("Website database connection failed: " . $e->getMessage());
    http_response_code(503);
    exit('Database connection failed.');
}

$adminUsernames = array_values(array_filter(array_map(
    'trim',
    explode(',', getenv('WEBSITE_ADMIN_USERNAMES') ?: '')
)));
