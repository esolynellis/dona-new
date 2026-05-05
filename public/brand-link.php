<?php
// Bypass any server-level exception handler
set_exception_handler(null);
set_error_handler(null);
restore_exception_handler();
restore_error_handler();
ob_start();

if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }
$root = '/www/wwwroot/dona-new';
$env = [];
foreach (file("$root/.env") as $ln) {
    $ln = trim($ln);
    if (!$ln || $ln[0] === '#' || !str_contains($ln, '=')) continue;
    [$k, $v] = explode('=', $ln, 2);
    $env[trim($k)] = trim($v, '"\'');
}

$out = [];

try {
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}",
        $env['DB_USERNAME'], $env['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    // Set connection charset and collation explicitly
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    $out['connected'] = true;
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    die(json_encode(['connection_error' => $e->getMessage()]));
}

$sqls = [
    'a_product_cols'  => "DESCRIBE products",
    'b_prod_count'    => "SELECT COUNT(*) c FROM products",
    'c_huipi_count'   => "SELECT COUNT(*) c FROM huipi_goods",
    'd_huipi_brands'  => "SELECT COUNT(*) c FROM huipi_brands WHERE delete_time=0 AND brand_id>1",
    'e_prod_sample'   => "SELECT id, goods_name, brand_id, brand_name FROM products LIMIT 3",
    'f_huipi_sample'  => "SELECT goods_id, goods_name, brand_id FROM huipi_goods LIMIT 2",
];

foreach ($sqls as $k => $sql) {
    try {
        $out[$k] = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $out[$k . '_err'] = $e->getMessage();
    }
}

ob_clean();
header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
