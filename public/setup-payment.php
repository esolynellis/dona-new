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

$results = [];

// 1. Check payment-related tables
if (isset($_GET['inspect'])) {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $payTables = array_filter($tables, fn($t) => stripos($t, 'pay') !== false || stripos($t, 'plugin') !== false || stripos($t, 'offline') !== false);
    echo json_encode(['tables' => array_values($payTables)], JSON_PRETTY_PRINT);
    exit;
}

// 2. Disable QPay - find and disable in settings
$qpayRows = $pdo->query("SELECT * FROM settings WHERE name LIKE '%qpay%' OR space LIKE '%qpay%' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$results['qpay_settings'] = $qpayRows;

// 3. Check plugin_settings or similar
$pluginRows = $pdo->query("SELECT * FROM settings WHERE name='status' AND (space LIKE '%pay%' OR space LIKE '%offline%') LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$results['plugin_payment_status'] = $pluginRows;

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
