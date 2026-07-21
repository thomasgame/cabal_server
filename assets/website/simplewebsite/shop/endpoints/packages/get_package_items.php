<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

$package_id = intval($_GET['id']);

$sql = "SELECT * FROM package_items WHERE package_item_id = ?";
$stmt = sqlsrv_query($conn, $sql, [$package_id]);

$items = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $items[] = $row;
}

echo json_encode($items);
