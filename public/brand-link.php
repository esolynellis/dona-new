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

// Sample products to see all fields
$sample = $pdo->query("SELECT * FROM products LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

// Check if products have any goods_id-like column that links to huipi_goods
$colNames = array_column($prodCols, 'Field');

// Try to find a link via goods_name match (with collation fix)
$matchTest = $pdo->query("
    SELECT p.id, p.goods_name, hg.goods_id, hg.brand_id, hb.brand_name
    FROM products p
    JOIN huipi_goods hg ON hg.goods_name COLLATE utf8mb4_general_ci = p.goods_name COLLATE utf8mb4_general_ci
    LEFT JOIN huipi_brands hb ON hb.brand_id = hg.brand_id
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Count matchable products
$matchCount = $pdo->query("
    SELECT COUNT(*) FROM products p
    JOIN huipi_goods hg ON hg.goods_name COLLATE utf8mb4_general_ci = p.goods_name COLLATE utf8mb4_general_ci
")->fetchColumn();

// Total products
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Check if there's a direct goods_id column in products
$hasGoodsId = in_array('goods_id', $colNames);

header('Content-Type: application/json');
echo json_encode([
    'product_columns'   => $colNames,
    'has_goods_id_col'  => $hasGoodsId,
    'total_products'    => $totalProducts,
    'match_by_name_count' => $matchCount,
    'match_sample'      => $matchTest,
    'product_sample'    => $sample,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
