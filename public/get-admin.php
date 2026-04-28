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
$tables = $pdo->query("SHOW TABLES LIKE '%admin%'")->fetchAll(PDO::FETCH_COLUMN);
$rows = [];
foreach ($tables as $t) {
    $rows[$t] = $pdo->query("SELECT * FROM `$t` LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
}
header('Content-Type: application/json');
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
