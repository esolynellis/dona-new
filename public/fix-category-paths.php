<?php
/**
 * fix-category-paths.php
 * Rebuild category_paths for all categories so frontend can find products
 * Usage: /fix-category-paths.php?key=dona2025
 *        /fix-category-paths.php?key=dona2025&run=1
 */
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die('Forbidden'); }

set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');

/* ── DB from .env ── */
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

/* ── ANALYSIS ── */
// Build full category tree in memory
$allCats = $pdo->query("SELECT id, parent_id FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$childrenOf = [];
$parentOf   = [];
foreach ($allCats as $c) {
    $childrenOf[$c['parent_id']][] = $c['id'];
    $parentOf[$c['id']] = $c['parent_id'];
}

// Get ancestor chain for any category
function getAncestors(int $id, array $parentOf): array {
    $chain = [];
    $cur = $id;
    $seen = [];
    while (isset($parentOf[$cur]) && $parentOf[$cur] != 0 && !isset($seen[$cur])) {
        $seen[$cur] = true;
        $cur = $parentOf[$cur];
        $chain[] = $cur;
    }
    return array_reverse($chain);
}

// Check categories with broken/missing paths
$existingPaths = $pdo->query("SELECT DISTINCT category_id FROM category_paths")->fetchAll(PDO::FETCH_COLUMN);
$existingPathSet = array_flip($existingPaths);

$allCatIds = array_column($allCats, 'id');
$missingPaths = array_filter($allCatIds, fn($id) => !isset($existingPathSet[$id]));

// Also find categories where path doesn't include full ancestor chain
$brokenPaths = [];
$pathsGrouped = [];
foreach ($pdo->query("SELECT category_id, path_id, level FROM category_paths ORDER BY category_id, level")->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $pathsGrouped[$row['category_id']][] = $row['path_id'];
}
foreach ($allCats as $c) {
    $id = $c['id'];
    $expectedAncestors = getAncestors($id, $parentOf);
    $expectedAncestors[] = $id; // include self
    $currentPaths = $pathsGrouped[$id] ?? [];
    // Check if all ancestors are in path
    foreach ($expectedAncestors as $anc) {
        if (!in_array($anc, $currentPaths)) {
            $brokenPaths[] = $id;
            break;
        }
    }
}

$toFix = array_unique(array_merge($missingPaths, $brokenPaths));

echo "=== Category Path Fix ===\n";
echo "Total categories     : " . count($allCats) . "\n";
echo "Missing paths        : " . count($missingPaths) . "\n";
echo "Broken paths         : " . count(array_unique($brokenPaths)) . "\n";
echo "To fix total         : " . count($toFix) . "\n\n";

if (!isset($_GET['run'])) {
    echo "-- Preview only. Add &run=1 to fix.\n";
    echo "\nSample broken category IDs: " . implode(', ', array_slice($toFix, 0, 20)) . "\n";
    die();
}

echo "Fixing " . count($toFix) . " categories...\n\n";

/* ── REBUILD PATHS ── */
$delStmt = $pdo->prepare("DELETE FROM category_paths WHERE category_id = ?");
$insStmt = $pdo->prepare(
    "INSERT INTO category_paths (category_id, path_id, level, created_at, updated_at)
     VALUES (?, ?, ?, NOW(), NOW())
     ON DUPLICATE KEY UPDATE level=VALUES(level), updated_at=NOW()"
);

$fixed = 0;
$errors = [];

foreach ($toFix as $catId) {
    try {
        $ancestors = getAncestors($catId, $parentOf);
        $fullChain = array_merge($ancestors, [$catId]); // ancestors + self

        // Delete old paths
        $delStmt->execute([$catId]);

        // Insert correct paths
        foreach ($fullChain as $level => $pathId) {
            $insStmt->execute([$catId, $pathId, $level]);
        }
        $fixed++;
    } catch (Exception $e) {
        $errors[] = "cat $catId: " . $e->getMessage();
    }
}

echo "Fixed: $fixed categories\n";
if ($errors) echo "Errors (" . count($errors) . "): " . implode('; ', array_slice($errors, 0, 5)) . "\n";

/* ── CLEAR LARAVEL CACHE ── */
echo "\nClearing cache...\n";
$cacheDir = "$root/storage/framework/cache/data";
$cleared = 0;
if (is_dir($cacheDir)) {
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        if ($file->isFile()) { @unlink($file->getPathname()); $cleared++; }
    }
}
echo "Cache cleared: $cleared files\n";

/* ── VERIFY ── */
echo "\n=== Verification ===\n";
$check2001 = $pdo->query("SELECT * FROM category_paths WHERE category_id=2001 ORDER BY level")->fetchAll(PDO::FETCH_ASSOC);
echo "Paths for cat 2001 (Архи):\n";
foreach ($check2001 as $p) echo "  level {$p['level']}: path_id={$p['path_id']}\n";

$check200101 = $pdo->query("SELECT * FROM category_paths WHERE category_id=200101 ORDER BY level")->fetchAll(PDO::FETCH_ASSOC);
echo "Paths for cat 200101 (Цайны архи):\n";
foreach ($check200101 as $p) echo "  level {$p['level']}: path_id={$p['path_id']}\n";

echo "\n*** DONE! Refresh the website to see products. ***\n";
