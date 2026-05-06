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

$catId  = 1000150;
$dryRun = ($_GET['confirm'] ?? '') !== 'yes';
$out    = [];

// Find products ONLY in this category (not in any other category)
$onlyInKazak = $pdo->query("
    SELECT p.id
    FROM products p
    INNER JOIN product_categories pc ON pc.product_id = p.id AND pc.category_id = $catId
    WHERE p.deleted_at IS NULL
      AND (
          SELECT COUNT(*) FROM product_categories pc2
          WHERE pc2.product_id = p.id AND pc2.category_id != $catId
      ) = 0
")->fetchAll(PDO::FETCH_COLUMN);

// Products also in other categories (won't be deleted, just unlinked from kazak)
$alsoElsewhere = $pdo->query("
    SELECT p.id, pd.name
    FROM products p
    INNER JOIN product_categories pc ON pc.product_id = p.id AND pc.category_id = $catId
    LEFT JOIN product_descriptions pd ON pd.product_id = p.id AND pd.locale = 'mn'
    WHERE p.deleted_at IS NULL
      AND (
          SELECT COUNT(*) FROM product_categories pc2
          WHERE pc2.product_id = p.id AND pc2.category_id != $catId
      ) > 0
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$out['category_id']        = $catId;
$out['only_in_kazak']      = count($onlyInKazak);
$out['also_elsewhere']     = count($alsoElsewhere);
$out['also_elsewhere_sample'] = $alsoElsewhere;
$out['dry_run']            = $dryRun;

if (!$dryRun) {
    // 1. Soft-delete products that are ONLY in this category
    if (!empty($onlyInKazak)) {
        $ph = implode(',', array_fill(0, count($onlyInKazak), '?'));
        $stmt = $pdo->prepare("UPDATE products SET deleted_at = NOW(), active = 0 WHERE id IN ($ph)");
        $stmt->execute($onlyInKazak);
        $out['products_deleted'] = $stmt->rowCount();
    } else {
        $out['products_deleted'] = 0;
    }

    // 2. Remove product_categories links for this category
    $stmt = $pdo->prepare("DELETE FROM product_categories WHERE category_id = ?");
    $stmt->execute([$catId]);
    $out['product_category_links_removed'] = $stmt->rowCount();

    // 3. Delete category_descriptions
    $stmt = $pdo->prepare("DELETE FROM category_descriptions WHERE category_id = ?");
    $stmt->execute([$catId]);
    $out['category_descriptions_deleted'] = $stmt->rowCount();

    // 4. Delete category_paths
    $tables = $pdo->query("SHOW TABLES LIKE 'category_paths'")->fetchColumn();
    if ($tables) {
        $stmt = $pdo->prepare("DELETE FROM category_paths WHERE category_id = ? OR path_id = ?");
        $stmt->execute([$catId, $catId]);
        $out['category_paths_deleted'] = $stmt->rowCount();
    }

    // 5. Delete the category itself
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$catId]);
    $out['category_deleted'] = $stmt->rowCount();

    $out['status'] = 'DONE';
} else {
    $out['status'] = 'DRY RUN – add ?confirm=yes to delete';
}

ob_clean();
header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
