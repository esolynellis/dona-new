<?php
/**
 * rollback-import.php
 * Remove any products imported from huipi_goods today, restore original state
 * Usage: /rollback-import.php?key=dona2025         (preview what will be deleted)
 *        /rollback-import.php?key=dona2025&run=1   (execute rollback)
 */
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die('Forbidden'); }

set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');

$root = '/www/wwwroot/dona-new';
$env  = [];
foreach (file("$root/.env") as $ln) {
    $ln = trim($ln);
    if (!$ln || $ln[0] === '#' || !str_contains($ln, '=')) continue;
    [$k, $v] = explode('=', $ln, 2);
    $env[trim($k)] = trim($v, '"\'');
}
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec("SET NAMES utf8mb4");

/* ── Find products inserted by the import script ── */
// The import used goods_id from huipi_goods and created_at = NOW() (today)
// We identify them by: goods_id exists in huipi_goods AND created today
$today = date('Y-m-d');

$imported = $pdo->query(
    "SELECT p.id, p.goods_id, p.price, p.created_at
     FROM products p
     INNER JOIN huipi_goods hg ON hg.goods_id = p.goods_id
     WHERE DATE(p.created_at) >= '$today'
     ORDER BY p.id"
)->fetchAll(PDO::FETCH_ASSOC);

$totalProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL")->fetchColumn();
$importedCount = count($imported);

echo "=== Import Rollback ===\n";
echo "Total products now     : $totalProducts\n";
echo "Imported today (to del): $importedCount\n\n";

if ($importedCount === 0) {
    echo "No products were imported today. Nothing to rollback.\n";
    echo "Your original data is intact.\n";
    die();
}

echo "Products to remove (first 20):\n";
foreach (array_slice($imported, 0, 20) as $p) {
    echo "  id={$p['id']} goods_id={$p['goods_id']} price={$p['price']} created={$p['created_at']}\n";
}
if ($importedCount > 20) echo "  ... and " . ($importedCount - 20) . " more\n";

if (!isset($_GET['run'])) {
    echo "\n-- Preview only. Add &run=1 to DELETE these imported products.\n";
    die();
}

/* ── EXECUTE ROLLBACK ── */
echo "\nExecuting rollback...\n";

$ids = array_column($imported, 'id');
$placeholders = implode(',', array_fill(0, count($ids), '?'));

// Delete in order: categories → skus → descriptions → products
$delCats  = $pdo->prepare("DELETE FROM product_categories WHERE product_id IN ($placeholders)");
$delSkus  = $pdo->prepare("DELETE FROM product_skus WHERE product_id IN ($placeholders)");
$delDescs = $pdo->prepare("DELETE FROM product_descriptions WHERE product_id IN ($placeholders)");
$delProds = $pdo->prepare("DELETE FROM products WHERE id IN ($placeholders)");

$delCats->execute($ids);
echo "  product_categories deleted: " . $delCats->rowCount() . "\n";

$delSkus->execute($ids);
echo "  product_skus deleted      : " . $delSkus->rowCount() . "\n";

$delDescs->execute($ids);
echo "  product_descriptions del  : " . $delDescs->rowCount() . "\n";

$delProds->execute($ids);
echo "  products deleted          : " . $delProds->rowCount() . "\n";

$remaining = $pdo->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL")->fetchColumn();
echo "\nProducts remaining: $remaining\n";

/* ── Clear cache ── */
echo "\nClearing cache...\n";
$dirs = [
    "$root/storage/framework/cache/data",
    "$root/storage/framework/views",
    "$root/storage/framework/sessions",
    "$root/bootstrap/cache",
];
$cleared = 0;
foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iter as $file) {
        if ($file->isFile()) { @unlink($file->getPathname()); $cleared++; }
    }
}
echo "Cache cleared: $cleared files\n";

echo "\n*** ROLLBACK COMPLETE. Original products restored. ***\n";
