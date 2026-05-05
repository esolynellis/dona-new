<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }
$root = '/www/wwwroot/dona-new';
$env = [];
foreach (file("$root/.env") as $line) {
    $line = trim($line);
    if (!$line || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, '"\'');
}
$pdo = new PDO("mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4", $env['DB_USERNAME'], $env['DB_PASSWORD']);

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$cols   = $pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_COLUMN, 0);
$cats   = $pdo->query("SELECT id, name FROM categories LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'all_tables'      => $tables,
    'product_columns' => $cols,
    'sample_cats'     => $cats,
    'cat_count'       => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
