<?php
/**
 * migrate-products.php
 * Copy active products from old DB → new (production) DB
 * Usage: /migrate-products.php?key=dona2025
 *        /migrate-products.php?key=dona2025&run=1&batch=0   (run batch 0)
 *        /migrate-products.php?key=dona2025&run=1&batch=1   (run batch 1, etc.)
 */
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die('Forbidden'); }

set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');

/* ── NEW DB (production) from .env ── */
$root = '/www/wwwroot/dona-new';
$env  = [];
foreach (file("$root/.env") as $ln) {
    $ln = trim($ln);
    if (!$ln || $ln[0] === '#' || !str_contains($ln, '=')) continue;
    [$k, $v] = explode('=', $ln, 2);
    $env[trim($k)] = trim($v, '"\'');
}
$new = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$new->exec("SET NAMES utf8mb4");

/* ── OLD DB ── */
$old = new PDO(
    "mysql:host=103.168.56.129;port=5531;dbname=mnshop;charset=utf8mb4",
    'mnshop', 'E334AKZYQra9NDps',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$old->exec("SET NAMES utf8mb4");

/* ── STATS (default view) ── */
$oldTotal  = $old->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL AND active=1")->fetchColumn();
$newTotal  = $new->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL")->fetchColumn();
$newIds    = $new->query("SELECT id FROM products WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
$newIdSet  = array_flip($newIds);

// find missing IDs
$oldIds = $old->query("SELECT id FROM products WHERE deleted_at IS NULL AND active=1 ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
$missing = array_values(array_filter($oldIds, fn($id) => !isset($newIdSet[$id])));

$BATCH = 200;
$totalBatches = ceil(count($missing) / $BATCH);

echo "=== DONA Product Migration ===\n";
echo "Old DB active products : $oldTotal\n";
echo "New DB products        : $newTotal\n";
echo "Missing (need to add)  : " . count($missing) . "\n";
echo "Batch size             : $BATCH\n";
echo "Total batches          : $totalBatches\n\n";

if (!isset($_GET['run'])) {
    echo "-- Preview only. Add &run=1&batch=0 to start migrating (one batch at a time).\n";
    echo "-- Or add &run=1&all=1 to run all batches at once (may timeout for large sets).\n";
    if (count($missing) > 0) {
        echo "\nFirst 10 missing IDs: " . implode(', ', array_slice($missing, 0, 10)) . "\n";
    }
    die();
}

/* ── RUN MIGRATION ── */
$batchNum = isset($_GET['all']) ? null : (int)($_GET['batch'] ?? 0);

if ($batchNum !== null) {
    // single batch
    $batchIds = array_slice($missing, $batchNum * $BATCH, $BATCH);
    if (!$batchIds) { echo "No products in batch $batchNum. All done!\n"; die(); }
    echo "Running batch $batchNum (" . count($batchIds) . " products)...\n\n";
    $result = migrateBatch($old, $new, $batchIds);
    echo $result;
    $done = ($batchNum + 1) * $BATCH;
    $remaining = count($missing) - $done;
    echo "\nBatch $batchNum done. Next: ?key=dona2025&run=1&batch=" . ($batchNum + 1);
    if ($remaining <= 0) echo "\n\n*** ALL BATCHES COMPLETE! ***";
    else echo " ($remaining remaining)";
} else {
    // all batches
    $total = count($missing);
    $done  = 0;
    for ($b = 0; $b < $totalBatches; $b++) {
        $batchIds = array_slice($missing, $b * $BATCH, $BATCH);
        echo "Batch $b (" . count($batchIds) . " products)...\n";
        $result = migrateBatch($old, $new, $batchIds);
        echo $result . "\n";
        $done += count($batchIds);
        echo "Progress: $done / $total\n\n";
        flush();
        ob_flush();
    }
    echo "\n*** ALL DONE! Migrated $done products. ***\n";
}

/* ════════════════════════════════════════════════════════ */
function migrateBatch(PDO $old, PDO $new, array $ids): string {
    if (!$ids) return "Empty batch.\n";
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $inserted = ['products' => 0, 'descriptions' => 0, 'skus' => 0, 'categories' => 0];
    $skipped  = 0;
    $errors   = [];

    /* ── PRODUCTS ── */
    $products = $old->prepare(
        "SELECT id, brand_id, images, price, video, position, active, variables, tax_class_id, created_at, updated_at
         FROM products WHERE id IN ($placeholders) AND deleted_at IS NULL"
    );
    $products->execute($ids);
    $rows = $products->fetchAll(PDO::FETCH_ASSOC);

    $insProduct = $new->prepare(
        "INSERT IGNORE INTO products (id, brand_id, images, price, video, position, active, variables, tax_class_id, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($rows as $p) {
        try {
            $insProduct->execute([
                $p['id'], $p['brand_id'] ?? 0,
                $p['images'] ?: '[]',
                $p['price'] ?? 0,
                $p['video'] ?? '',
                $p['position'] ?? 0,
                $p['active'] ?? 1,
                $p['variables'] ?: '[]',
                $p['tax_class_id'] ?? 0,
                $p['created_at'], $p['updated_at'],
            ]);
            if ($insProduct->rowCount()) $inserted['products']++;
            else $skipped++;
        } catch (Exception $e) {
            $errors[] = "product {$p['id']}: " . $e->getMessage();
        }
    }

    /* ── PRODUCT_DESCRIPTIONS ── */
    $descs = $old->prepare(
        "SELECT product_id, locale, name, content, meta_title, meta_description, created_at, updated_at
         FROM product_descriptions WHERE product_id IN ($placeholders)"
    );
    $descs->execute($ids);
    $descRows = $descs->fetchAll(PDO::FETCH_ASSOC);

    $insDesc = $new->prepare(
        "INSERT IGNORE INTO product_descriptions (product_id, locale, name, content, meta_title, meta_description, meta_keyword, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, '', ?, ?)"
    );
    foreach ($descRows as $d) {
        try {
            $insDesc->execute([
                $d['product_id'], $d['locale'],
                $d['name'] ?? '',
                $d['content'] ?? '',
                $d['meta_title'] ?? '',
                $d['meta_description'] ?? '',
                $d['created_at'], $d['updated_at'],
            ]);
            if ($insDesc->rowCount()) $inserted['descriptions']++;
        } catch (Exception $e) {
            $errors[] = "desc {$d['product_id']}: " . $e->getMessage();
        }
    }

    /* ── PRODUCT_SKUS ── */
    $skus = $old->prepare(
        "SELECT id, product_id, variants, position, images, model, sku, price, origin_price, cost_price, quantity, is_default, created_at, updated_at
         FROM product_skus WHERE product_id IN ($placeholders)"
    );
    $skus->execute($ids);
    $skuRows = $skus->fetchAll(PDO::FETCH_ASSOC);

    $insSku = $new->prepare(
        "INSERT IGNORE INTO product_skus (id, product_id, variants, position, images, model, sku, price, origin_price, cost_price, quantity, is_default, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($skuRows as $s) {
        try {
            $insSku->execute([
                $s['id'], $s['product_id'],
                $s['variants'] ?? '""',
                $s['position'] ?? 0,
                $s['images'],
                $s['model'] ?? 'default',
                $s['sku'] ?? '',
                $s['price'] ?? 0,
                $s['origin_price'] ?? 0,
                $s['cost_price'] ?? 0,
                $s['quantity'] ?? 0,
                $s['is_default'] ?? 1,
                $s['created_at'], $s['updated_at'],
            ]);
            if ($insSku->rowCount()) $inserted['skus']++;
        } catch (Exception $e) {
            $errors[] = "sku {$s['id']}: " . $e->getMessage();
        }
    }

    /* ── PRODUCT_CATEGORIES ── */
    // Only copy if category exists in new DB
    $newCats = $new->query("SELECT id FROM categories")->fetchAll(PDO::FETCH_COLUMN);
    $newCatSet = array_flip($newCats);

    $cats = $old->prepare(
        "SELECT product_id, category_id FROM product_categories WHERE product_id IN ($placeholders)"
    );
    $cats->execute($ids);
    $catRows = $cats->fetchAll(PDO::FETCH_ASSOC);

    $insCat = $new->prepare(
        "INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?, ?)"
    );
    foreach ($catRows as $c) {
        if (!isset($newCatSet[$c['category_id']])) continue; // skip unknown categories
        try {
            $insCat->execute([$c['product_id'], $c['category_id']]);
            if ($insCat->rowCount()) $inserted['categories']++;
        } catch (Exception $e) {
            $errors[] = "cat {$c['product_id']}/{$c['category_id']}: " . $e->getMessage();
        }
    }

    $out  = "  products: +{$inserted['products']} (skipped: $skipped)\n";
    $out .= "  descriptions: +{$inserted['descriptions']}\n";
    $out .= "  skus: +{$inserted['skus']}\n";
    $out .= "  categories: +{$inserted['categories']}\n";
    if ($errors) $out .= "  ERRORS (" . count($errors) . "): " . implode('; ', array_slice($errors, 0, 3)) . "\n";
    return $out;
}
