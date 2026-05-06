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
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

$dryRun = ($_GET['confirm'] ?? '') !== 'yes';
$out    = [];

// Find Kazakh products - search in goods_name and product_descriptions
$kazakProducts = $pdo->query("
    SELECT DISTINCT p.id, p.goods_name, pd.name as desc_name, p.active, p.deleted_at
    FROM products p
    LEFT JOIN product_descriptions pd ON pd.product_id = p.id
    WHERE (
        p.goods_name LIKE '%казак%'
        OR p.goods_name LIKE '%Казак%'
        OR p.goods_name LIKE '%казах%'
        OR p.goods_name LIKE '%Казах%'
        OR p.goods_name LIKE '%казак%'
        OR pd.name LIKE '%казак%'
        OR pd.name LIKE '%Казак%'
        OR pd.name LIKE '%казах%'
    )
    AND p.deleted_at IS NULL
")->fetchAll(PDO::FETCH_ASSOC);

// Also find uncategorized products (not in product_categories at all)
$uncategorized = $pdo->query("
    SELECT p.id, p.goods_name, pd.name as desc_name
    FROM products p
    LEFT JOIN product_descriptions pd ON pd.product_id = p.id AND pd.locale = 'mn'
    LEFT JOIN product_categories pc ON pc.product_id = p.id
    WHERE pc.product_id IS NULL
      AND p.deleted_at IS NULL
      AND p.active = 1
    GROUP BY p.id
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

$out['kazak_products']   = $kazakProducts;
$out['kazak_count']      = count($kazakProducts);
$out['uncategorized_sample'] = $uncategorized;
$out['dry_run']          = $dryRun;

if (!$dryRun && !empty($kazakProducts)) {
    $ids = array_column($kazakProducts, 'id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    // Soft delete (set deleted_at)
    $stmt = $pdo->prepare("UPDATE products SET deleted_at = NOW() WHERE id IN ($ph)");
    $stmt->execute($ids);
    $out['deleted_count'] = $stmt->rowCount();
    $out['status'] = 'DONE - soft deleted';
} else if ($dryRun) {
    $out['status'] = 'DRY RUN – add ?confirm=yes to delete';
}

ob_clean();
header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
