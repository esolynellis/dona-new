<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }
$root = '/www/wwwroot/dona-new';
$env = [];
foreach (file("$root/.env") as $ln) {
    $ln = trim($ln);
    if (!$ln || $ln[0] === '#' || !str_contains($ln, '=')) continue;
    [$k, $v] = explode('=', $ln, 2);
    $env[trim($k)] = trim($v, '"\'');
}
$pdo = new PDO("mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4", $env['DB_USERNAME'], $env['DB_PASSWORD']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$out = [];

$queries = [
    'q1_describe_products' => "DESCRIBE products",
    'q2_count_products'    => "SELECT COUNT(*) FROM products",
    'q3_count_huipi'       => "SELECT COUNT(*) FROM huipi_goods",
    'q4_huipi_brand_id'    => "SELECT COUNT(*) FROM huipi_goods WHERE brand_id > 1",
    'q5_sample_product'    => "SELECT id, goods_name, brand_id, brand_name FROM products LIMIT 2",
];

foreach ($queries as $key => $sql) {
    try {
        $result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $out[$key] = $result;
    } catch (Exception $e) {
        $out[$key . '_ERROR'] = $e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
