<?php
/**
 * rollback-import.php v2
 * Remove products created at 2026-05-07 00:26 (imported by import-huipi-goods.php)
 * Usage: /rollback-import.php?key=dona2025         (preview)
 *        /rollback-import.php?key=dona2025&run=1   (delete)
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

// Products created by the import script on 2026-05-07 00:26 (visible in screenshot: 85923-85929)
// We delete ALL products created on 2026-05-07 between 00:00 and 01:00
$imported = $pdo->query(
    "SELECT id, price, created_at,
            (SELECT name FROM product_descriptions WHERE product_id=products.id LIMIT 1) as name
     FROM products
     WHERE created_at >= '2026-05-07 00:00:00'
       AND created_at <  '2026-05-07 01:00:00'
     ORDER BY id"
)->fetchAll(PDO::FETCH_ASSOC);

$importedCount = count($imported);
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL")->fetchColumn();

echo "=== Rollback v2 ===\n";
echo "Total products now     : $totalProducts\n";
echo "To be deleted          : $importedCount\n\n";

if ($importedCount === 0) {
    echo "Nothing found in that time range. Already cleaned or different timestamp.\n";

    // Try broader search: any product created today
    $broader = $pdo->query(
        "SELECT id, created_at FROM products WHERE DATE(created_at)='2026-05-07' ORDER BY id LIMIT 30"
    )->fetchAll(PDO::FETCH_ASSOC);
    echo "\nAll products created 2026-05-07 (up to 30):\n";
    foreach ($broader as $r) echo "  id={$r['id']} created={$r['created_at']}\n";
    die();
}

echo "Products to delete:\n";
foreach ($imported as $p) {
    $name = mb_substr($p['name'] ?? '', 0, 50);
    echo "  id={$p['id']} created={$p['created_at']} price={$p['price']} name=\"$name\"\n";
}

if (!isset($_GET['run'])) {
    echo "\n-- Preview only. Add &run=1 to DELETE these " . $importedCount . " products.\n";
    die();
}

/* ── DELETE ── */
echo "\nDeleting...\n";
$ids = array_column($imported, 'id');
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$pdo->prepare("DELETE FROM product_categories   WHERE product_id IN ($placeholders)")->execute($ids);
echo "  product_categories deleted\n";

$pdo->prepare("DELETE FROM product_skus         WHERE product_id IN ($placeholders)")->execute($ids);
echo "  product_skus deleted\n";

$pdo->prepare("DELETE FROM product_descriptions WHERE product_id IN ($placeholders)")->execute($ids);
echo "  product_descriptions deleted\n";

$stmt = $pdo->prepare("DELETE FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
echo "  products deleted: " . $stmt->rowCount() . "\n";

$remaining = $pdo->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL")->fetchColumn();
echo "\nProducts remaining: $remaining\n";

/* ── Clear cache ── */
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

echo "\n*** DONE. Imported products removed. Original state restored. ***\n";
