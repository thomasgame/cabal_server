<?php
// items.php
header('Content-Type: application/json');

$filePath = 'cabal_msg.dec';

if (!file_exists($filePath)) {
    echo json_encode(['error' => 'File not found']);
    exit;
}

$contents = file_get_contents($filePath);
$contents = mb_convert_encoding($contents, 'UTF-8', 'auto');

$start = stripos($contents, '<item_msg>');
$end = stripos($contents, '</item_msg>');

if ($start === false || $end === false) {
    echo json_encode(['error' => 'No <item_msg> block found']);
    exit;
}

$block = substr($contents, $start + strlen('<item_msg>'), $end - $start - strlen('<item_msg>'));
$lines = explode("\n", $block);

$items = [];
$query = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';
$max = 100;

foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '') continue;

    if (preg_match('/<msg id="(item\d+)" cont="([^"]+)"\/>/', $line, $matches)) {
        $id = preg_replace('/\D/', '', $matches[1]);
        $name = $matches[2];

        if ($query && strpos(strtolower($name), $query) === false) continue;

        $items[] = ['id' => $id, 'name' => $name];
        if (count($items) >= $max) break;
    }
}

echo json_encode($items, JSON_UNESCAPED_UNICODE);
