<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

$sql = "SELECT * FROM items WHERE is_package = 0";
$stmt = sqlsrv_query($conn, $sql);

$items = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $items[] = $row;
}

echo json_encode($items);
