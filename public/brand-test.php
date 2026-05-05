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

$brand = '云南白药';
$out = [];

// Direct product query with brand filter
$stmt = $pdo->prepare("SELECT id, goods_name, brand_name, brand_id, active, deleted_at FROM products WHERE brand_name = ? LIMIT 5");
$stmt->execute([$brand]);
$out['products_by_brand'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count active products per top brand
$out['active_brand_counts'] = $pdo->query("
    SELECT brand_name, COUNT(*) cnt
    FROM products
    WHERE brand_name IS NOT NULL AND brand_name != ''
      AND active = 1
      AND deleted_at IS NULL
    GROUP BY brand_name
    ORDER BY cnt DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Active totals
$out['active_with_brand'] = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE brand_name IS NOT NULL AND brand_name != '' AND active=1 AND deleted_at IS NULL")->fetchColumn();
$out['total_active'] = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE active=1 AND deleted_at IS NULL")->fetchColumn();

ob_clean();
header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
