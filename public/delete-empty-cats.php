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

// Find categories with zero products (direct + through subcategories)
// Step 1: for each category, count products including children
$emptyCats = $pdo->query("
    SELECT c.id, c.parent_id,
           (SELECT COUNT(*) FROM product_categories pc WHERE pc.category_id = c.id) AS direct_count
    FROM categories c
    ORDER BY c.parent_id DESC, c.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Build a set of category IDs that have at least one product (directly)
$hasProducts = [];
foreach ($emptyCats as $cat) {
    if ($cat['direct_count'] > 0) {
        $hasProducts[$cat['id']] = true;
        // Also mark all ancestors as non-empty
    }
}

// Mark parent categories as non-empty if any child has products
$parentMap = [];
foreach ($emptyCats as $cat) {
    $parentMap[$cat['id']] = $cat['parent_id'];
}
foreach (array_keys($hasProducts) as $id) {
    $pid = $parentMap[$id] ?? 0;
    while ($pid) {
        $hasProducts[$pid] = true;
        $pid = $parentMap[$pid] ?? 0;
    }
}

// Empty = not in hasProducts
$toDelete = [];
foreach ($emptyCats as $cat) {
    if (!isset($hasProducts[$cat['id']])) {
        $toDelete[] = $cat['id'];
    }
}

$out['total_categories']  = count($emptyCats);
$out['empty_count']       = count($toDelete);
$out['dry_run']           = $dryRun;
$out['sample_to_delete']  = array_slice($toDelete, 0, 20);

if (!$dryRun && !empty($toDelete)) {
    // Check which related tables exist
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    $chunks = array_chunk($toDelete, 100);
    $deleted = ['categories' => 0];

    foreach ($chunks as $chunk) {
        $ph = implode(',', array_fill(0, count($chunk), '?'));

        // Delete from category_descriptions if exists
        if (in_array('category_descriptions', $tables)) {
            $stmt = $pdo->prepare("DELETE FROM category_descriptions WHERE category_id IN ($ph)");
            $stmt->execute($chunk);
            $deleted['category_descriptions'] = ($deleted['category_descriptions'] ?? 0) + $stmt->rowCount();
        }

        // Delete from category_paths if exists
        if (in_array('category_paths', $tables)) {
            $stmt = $pdo->prepare("DELETE FROM category_paths WHERE category_id IN ($ph) OR path_id IN ($ph)");
            $stmt->execute(array_merge($chunk, $chunk));
            $deleted['category_paths'] = ($deleted['category_paths'] ?? 0) + $stmt->rowCount();
        }

        // Delete from product_categories (just in case - should be 0)
        $stmt = $pdo->prepare("DELETE FROM product_categories WHERE category_id IN ($ph)");
        $stmt->execute($chunk);
        $deleted['product_categories'] = ($deleted['product_categories'] ?? 0) + $stmt->rowCount();

        // Delete the categories themselves
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id IN ($ph)");
        $stmt->execute($chunk);
        $deleted['categories'] += $stmt->rowCount();
    }

    $out['deleted'] = $deleted;
    $out['status']  = 'DONE';
} else if ($dryRun) {
    $out['status'] = 'DRY RUN – add ?confirm=yes to actually delete';
}

ob_clean();
header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
