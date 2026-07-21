<?php
$request = $_GET['request'] ?? '';
$path = explode('/', $request);

// Basic router
switch ($path[0]) {
    case 'items':
        if (($path[1] ?? '') === 'get') {
            include 'endpoints/items/get_all.php';
        } elseif (($path[1] ?? '') === 'view' && isset($path[2])) {
            $_GET['id'] = $path[2];
            include 'endpoints/items/get_one.php';
        }
        break;

    case 'packages':
        if (($path[1] ?? '') === 'view' && isset($path[2])) {
            $_GET['id'] = $path[2];
            include 'endpoints/packages/get_package_items.php';
        }
        break;

    case 'categories':
        include 'endpoints/categories/get_all.php';
        break;

    case 'admin':
        if (($path[1] ?? '') === 'items' && ($path[2] ?? '') === 'create') {
            include 'endpoints/admin/items/create_item.php';
        } elseif (($path[1] ?? '') === 'packages' && ($path[2] ?? '') === 'create') {
            include 'endpoints/admin/items/create_package.php';
        }
        break;

    case 'checkout':
        include 'endpoints/checkout/checkout.php';
        break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Invalid endpoint"]);
        break;
}