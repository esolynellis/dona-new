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

// Brands table structure + sample data
$brandCols = $pdo->query("DESCRIBE brands")->fetchAll(PDO::FETCH_COLUMN, 0);
$brands    = $pdo->query("SELECT * FROM brands LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
$brandCount= $pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn();

// Products with brand_name (sample)
$branded   = $pdo->query("SELECT id, brand_id, brand_name, goods_name, images FROM products WHERE brand_name IS NOT NULL AND brand_name != '' LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$brandedCount = $pdo->query("SELECT COUNT(*) FROM products WHERE brand_name IS NOT NULL AND brand_name != ''")->fetchColumn();

// Count products per brand_name
$perBrand = $pdo->query("SELECT brand_name, COUNT(*) as cnt FROM products WHERE brand_name IS NOT NULL AND brand_name != '' GROUP BY brand_name ORDER BY cnt DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

// Check existing brand routes/pages
$routes = glob("$root/beike/*/Routes/*.php");

header('Content-Type: application/json');
echo json_encode([
    'brand_cols'     => $brandCols,
    'brands_sample'  => $brands,
    'brand_count'    => $brandCount,
    'products_with_brand' => $brandedCount,
    'top_brands'     => $perBrand,
    'route_files'    => $routes,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
