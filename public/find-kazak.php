<?php
set_exception_handler(null); restore_exception_handler();
ob_start();
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }
$root = '/www/wwwroot/dona-new';
$env  = [];
foreach (file("$root/.env") as $ln) {
    $ln = trim($ln);
    if (!$ln || $ln[0] === '#' || !str_contains($ln, '=')) continue;
    [$k, $v] = explode('=', $ln, 2);
    $env[trim($k)] = trim($v, '"\'');
}
$pdo = new PDO("mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}", $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

// Search categories for казак/казах
$cats = $pdo->query("
    SELECT c.id, cd.name, c.parent_id, c.active,
           (SELECT COUNT(*) FROM product_categories pc WHERE pc.category_id = c.id) as prod_count
    FROM categories c
    LEFT JOIN category_descriptions cd ON cd.category_id = c.id
    WHERE cd.name LIKE '%казак%' OR cd.name LIKE '%Казак%'
       OR cd.name LIKE '%казах%' OR cd.name LIKE '%Казах%'
       OR cd.name LIKE '%قازاق%'
")->fetchAll(PDO::FETCH_ASSOC);

// Search product descriptions
$prods = $pdo->query("
    SELECT p.id, p.goods_name, pd.name, p.active
    FROM products p
    LEFT JOIN product_descriptions pd ON pd.product_id = p.id
    WHERE pd.name LIKE '%казак%' OR pd.name LIKE '%Казак%'
       OR pd.name LIKE '%казах%' OR pd.name LIKE '%Казах%'
       OR p.goods_name LIKE '%казак%' OR p.goods_name LIKE '%Казак%'
       OR p.goods_name LIKE '%كازاخ%'
    AND p.deleted_at IS NULL
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// All categories - show ones with 0-2 products
$smallCats = $pdo->query("
    SELECT c.id, cd.name, c.parent_id,
           (SELECT COUNT(*) FROM product_categories pc WHERE pc.category_id = c.id) as cnt
    FROM categories c
    LEFT JOIN category_descriptions cd ON cd.category_id = c.id AND cd.locale = 'mn'
    HAVING cnt <= 2 AND cnt > 0
    ORDER BY cnt ASC
    LIMIT 30
")->fetchAll(PDO::FETCH_ASSOC);

ob_clean();
header('Content-Type: application/json');
echo json_encode([
    'cats_with_kazak' => $cats,
    'prods_with_kazak' => $prods,
    'cats_with_1_2_products' => $smallCats,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
