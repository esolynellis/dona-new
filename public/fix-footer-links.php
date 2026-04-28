<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';

$env = [];
foreach (file("$root/.env") as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, '"\'');
}

$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Discover table structure
if (isset($_GET['inspect'])) {
    $cols = $pdo->query("DESCRIBE settings")->fetchAll(PDO::FETCH_ASSOC);
    $sample = $pdo->query("SELECT * FROM settings LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['columns' => $cols, 'sample' => $sample], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Find footer_setting row
$rows = $pdo->query("SELECT * FROM settings WHERE name LIKE '%footer%' OR value LIKE '%gitbook%' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo json_encode(['error' => 'no footer rows found', 'hint' => 'add &inspect=1 to see table structure']);
    exit;
}

echo json_encode(['found' => count($rows), 'rows' => array_map(fn($r) => array_diff_key($r, ['value'=>1]), $rows)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
