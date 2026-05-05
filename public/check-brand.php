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

// 1. DB-д brand хүснэгт байгаа эсэх
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$results['all_tables'] = $tables;
$brandTables = array_filter($tables, fn($t) => stripos($t, 'brand') !== false || stripos($t, 'maker') !== false || stripos($t, 'manufact') !== false);
$results['brand_tables'] = array_values($brandTables);

// 2. Products хүснэгтэд brand_id байгаа эсэх
$cols = $pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_ASSOC);
$results['product_columns'] = array_column($cols, 'Field');

// 3. Tags хүснэгт байгаа эсэх
$tagTables = array_filter($tables, fn($t) => stripos($t, 'tag') !== false);
$results['tag_tables'] = array_values($tagTables);

// 4. Plugins дотор brand plugin байгаа эсэх
$plugins = $pdo->query("SELECT code, status FROM plugins")->fetchAll(PDO::FETCH_ASSOC);
$results['plugins'] = $plugins;

// 5. Categories хүснэгтийн бүтэц
$catCols = $pdo->query("DESCRIBE categories")->fetchAll(PDO::FETCH_ASSOC);
$results['category_columns'] = array_column($catCols, 'Field');

// 6. Нийт category тоо
$catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$results['category_count'] = $catCount;

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
