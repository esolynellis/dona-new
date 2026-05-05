<?php
set_exception_handler(null);
set_error_handler(null);
restore_exception_handler();
restore_error_handler();
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

try {
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}",
        $env['DB_USERNAME'], $env['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    die(json_encode(['error' => 'connection: ' . $e->getMessage()]));
}

$out = [];

try {
    // Step 1: Sync brand_name from local brands table
    // products.brand_id (int) -> brands.id (int) -> brands.name
    $stmt = $pdo->exec("
        UPDATE products p
        INNER JOIN brands b ON b.id = p.brand_id
        SET p.brand_name = b.name
        WHERE p.brand_id > 0
          AND (p.brand_name IS NULL OR p.brand_name = '')
          AND p.deleted_at IS NULL
    ");
    $out['step1_local_brands_updated'] = $stmt;
} catch (Exception $e) {
    $out['step1_error'] = $e->getMessage();
}

try {
    // Step 2: Sync brand_name from huipi system via goods_id (integer join - no collation issues)
    // products.goods_id (int) -> huipi_goods.goods_id (int) -> huipi_goods.brand_id -> huipi_brands.brand_name
    $stmt = $pdo->exec("
        UPDATE products p
        INNER JOIN huipi_goods hg ON hg.goods_id = p.goods_id
        INNER JOIN huipi_brands hb ON hb.brand_id = hg.brand_id
        SET p.brand_name = hb.brand_name
        WHERE hg.brand_id > 1
          AND hg.delete_time = 0
          AND hb.delete_time = 0
          AND (p.brand_name IS NULL OR p.brand_name = '')
          AND p.deleted_at IS NULL
    ");
    $out['step2_huipi_brands_updated'] = $stmt;
} catch (Exception $e) {
    $out['step2_error'] = $e->getMessage();
}

try {
    // Final counts
    $out['products_with_brand_name']    = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE brand_name IS NOT NULL AND brand_name != '' AND deleted_at IS NULL")->fetchColumn();
    $out['products_without_brand_name'] = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE (brand_name IS NULL OR brand_name = '') AND deleted_at IS NULL")->fetchColumn();
    $out['top_brands'] = $pdo->query("SELECT brand_name, COUNT(*) cnt FROM products WHERE brand_name IS NOT NULL AND brand_name != '' GROUP BY brand_name ORDER BY cnt DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $out['count_error'] = $e->getMessage();
}

ob_clean();
header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
