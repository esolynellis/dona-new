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
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD']
);

$results = [];

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$results['brand_tables']  = array_values(array_filter($tables, fn($t) => stripos($t,'brand')!==false||stripos($t,'maker')!==false));
$results['tag_tables']    = array_values(array_filter($tables, fn($t) => stripos($t,'tag')!==false));

// Products columns
$cols = $pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_ASSOC);
$results['product_columns'] = array_column($cols, 'Field');

// Categories
$catCols = $pdo->query("DESCRIBE categories")->fetchAll(PDO::FETCH_ASSOC);
$results['category_columns'] = array_column($catCols, 'Field');
$results['category_count'] = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Sample categories
$cats = $pdo->query("SELECT id, name FROM categories LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$results['sample_categories'] = $cats;

// Plugins columns
$pluginCols = $pdo->query("DESCRIBE plugins")->fetchAll(PDO::FETCH_ASSOC);
$results['plugin_columns'] = array_column($pluginCols, 'Field');

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
