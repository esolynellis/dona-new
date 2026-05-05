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

// All product columns
$prodCols = $pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_ASSOC);
$colNames = array_column($prodCols, 'Field');

// Total products
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Sample products (just key fields)
$sample = $pdo->query("SELECT id, goods_name, brand_id, brand_name FROM products LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

// Check table collations
$collations = $pdo->query("
    SELECT TABLE_NAME, TABLE_COLLATION
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('products', 'huipi_goods', 'huipi_brands', 'brands')
")->fetchAll(PDO::FETCH_ASSOC);

// Check column collations
$colCollations = $pdo->query("
    SELECT TABLE_NAME, COLUMN_NAME, COLLATION_NAME
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('products', 'huipi_goods')
    AND COLUMN_NAME = 'goods_name'
")->fetchAll(PDO::FETCH_ASSOC);

// Count huipi_goods with non-default brand
$huipiBrandCount = $pdo->query("SELECT COUNT(*) FROM huipi_goods WHERE brand_id > 1")->fetchColumn();
$huipiTotal = $pdo->query("SELECT COUNT(*) FROM huipi_goods")->fetchColumn();

header('Content-Type: application/json');
echo json_encode([
    'product_columns'    => $colNames,
    'total_products'     => $totalProducts,
    'product_sample'     => $sample,
    'table_collations'   => $collations,
    'col_collations'     => $colCollations,
    'huipi_total'        => $huipiTotal,
    'huipi_with_brand'   => $huipiBrandCount,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
