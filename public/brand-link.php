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

$out = [];

// Product columns
$out['product_columns'] = $pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_COLUMN, 0);

// Total products
$out['total_products'] = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// huipi totals
$out['huipi_total'] = (int)$pdo->query("SELECT COUNT(*) FROM huipi_goods")->fetchColumn();
$out['huipi_with_brand'] = (int)$pdo->query("SELECT COUNT(*) FROM huipi_goods WHERE brand_id > 1")->fetchColumn();

// Sample product
$out['product_sample'] = $pdo->query("SELECT * FROM products LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);

// Sample huipi_brands
$out['huipi_brands_count'] = (int)$pdo->query("SELECT COUNT(*) FROM huipi_brands")->fetchColumn();

header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
