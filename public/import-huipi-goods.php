<?php
/**
 * import-huipi-goods.php
 * Import products from huipi_goods → products table (makes them visible to customers)
 * Usage: /import-huipi-goods.php?key=dona2025              (preview / count)
 *        /import-huipi-goods.php?key=dona2025&run=1&batch=0 (run batch 0)
 *        /import-huipi-goods.php?key=dona2025&run=1&all=1   (run all batches)
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

/* ── Find huipi_goods NOT yet in products ── */
$missing = $pdo->query(
    "SELECT hg.goods_id FROM huipi_goods hg
     WHERE hg.status = 1
       AND NOT EXISTS (
           SELECT 1 FROM products p WHERE p.goods_id = hg.goods_id
       )
     ORDER BY hg.goods_id"
)->fetchAll(PDO::FETCH_COLUMN);

$totalMissing = count($missing);
$totalHuipi   = $pdo->query("SELECT COUNT(*) FROM huipi_goods WHERE status=1")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL")->fetchColumn();

$BATCH = 100;
$totalBatches = ceil($totalMissing / $BATCH);

echo "=== Huipi Goods Import ===\n";
echo "huipi_goods (active)   : $totalHuipi\n";
echo "products (current)     : $totalProducts\n";
echo "Not yet imported       : $totalMissing\n";
echo "Batch size             : $BATCH\n";
echo "Total batches needed   : $totalBatches\n\n";

if (!isset($_GET['run'])) {
    echo "-- Preview only. Add &run=1&batch=0 to import (one batch at a time).\n";
    echo "-- Or add &run=1&all=1 to import all at once.\n";
    if ($totalMissing > 0) {
        echo "\nFirst 10 missing goods_id: " . implode(', ', array_slice($missing, 0, 10)) . "\n";
    }
    die();
}

/* ── RUN IMPORT ── */
$batchNum = isset($_GET['all']) ? null : (int)($_GET['batch'] ?? 0);

if ($batchNum !== null) {
    $batchIds = array_slice($missing, $batchNum * $BATCH, $BATCH);
    if (!$batchIds) { echo "No items in batch $batchNum. All done!\n"; die(); }
    echo "Running batch $batchNum (" . count($batchIds) . " goods)...\n\n";
    $result = importBatch($pdo, $batchIds);
    echo $result;
    $nextBatch = $batchNum + 1;
    $remaining = $totalMissing - ($nextBatch * $BATCH);
    echo "\nBatch $batchNum done.";
    if ($remaining <= 0) echo "\n\n*** ALL BATCHES COMPLETE! Refresh the website. ***\n";
    else echo " Next: ?key=dona2025&run=1&batch=$nextBatch ($remaining remaining)\n";
} else {
    $done = 0;
    for ($b = 0; $b < $totalBatches; $b++) {
        $batchIds = array_slice($missing, $b * $BATCH, $BATCH);
        echo "Batch $b (" . count($batchIds) . " goods)...\n";
        echo importBatch($pdo, $batchIds);
        $done += count($batchIds);
        echo "Progress: $done / $totalMissing\n\n";
        flush();
        ob_flush();
    }
    echo "\n*** ALL DONE! Imported $done products. ***\n";
    echo "\nClearing cache...\n";
    clearLaravelCache($root);
    echo "Cache cleared.\n";
    echo "\nRefresh the website — products should now be visible to customers.\n";
}

/* ════════════════════════════════════════════════════════ */
function importBatch(PDO $pdo, array $goodsIds): string {
    if (!$goodsIds) return "Empty batch.\n";

    $placeholders = implode(',', array_fill(0, count($goodsIds), '?'));
    $out = '';
    $inserted = ['products' => 0, 'descriptions' => 0, 'skus' => 0, 'categories' => 0];
    $errors = [];

    /* ── Fetch huipi_goods rows ── */
    $stmt = $pdo->prepare(
        "SELECT goods_id, goods_name, goods_cover, goods_image,
                goods_mall_category, cash_price_small, goods_code,
                stock, brand_id, status, quality
         FROM huipi_goods
         WHERE goods_id IN ($placeholders)"
    );
    $stmt->execute($goodsIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ── Get valid category IDs ── */
    $validCats = $pdo->query("SELECT id FROM categories")->fetchAll(PDO::FETCH_COLUMN);
    $validCatSet = array_flip($validCats);

    /* ── Prepare inserts ── */
    $insProduct = $pdo->prepare(
        "INSERT IGNORE INTO products
            (goods_id, brand_id, images, price, video, position, active, variables, tax_class_id, created_at, updated_at)
         VALUES (?, ?, ?, ?, '', 0, 1, '[]', 0, NOW(), NOW())"
    );

    $insDesc = $pdo->prepare(
        "INSERT IGNORE INTO product_descriptions
            (product_id, locale, name, content, meta_title, meta_description, meta_keyword, created_at, updated_at)
         VALUES (?, 'mn', ?, '', '', '', '', NOW(), NOW())"
    );

    $insSku = $pdo->prepare(
        "INSERT IGNORE INTO product_skus
            (product_id, variants, position, images, model, sku, price, origin_price, cost_price, quantity, is_default, created_at, updated_at)
         VALUES (?, '\"\"', 0, ?, 'default', ?, ?, ?, ?, 1, NOW(), NOW())"
    );

    $insCat = $pdo->prepare(
        "INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?, ?)"
    );

    foreach ($rows as $g) {
        try {
            /* Build images JSON */
            $imgArr = [];
            if (!empty($g['goods_image'])) {
                $raw = $g['goods_image'];
                // Could be JSON array or comma-separated or single URL
                if ($raw[0] === '[') {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) $imgArr = $decoded;
                } elseif (str_contains($raw, ',')) {
                    $imgArr = array_map('trim', explode(',', $raw));
                } else {
                    $imgArr = [$raw];
                }
            } elseif (!empty($g['goods_cover'])) {
                $imgArr = [$g['goods_cover']];
            }
            $imagesJson = json_encode(array_values(array_filter($imgArr)));

            $price = (float)($g['cash_price_small'] ?? 0);
            $qty   = (int)($g['stock'] ?? 0);
            $sku   = $g['goods_code'] ?? '';
            $name  = $g['goods_name'] ?? '';
            $brandId = (int)($g['brand_id'] ?? 0);

            /* Insert product */
            $insProduct->execute([$g['goods_id'], $brandId, $imagesJson, $price]);
            if (!$insProduct->rowCount()) continue; // already exists

            $productId = (int)$pdo->lastInsertId();
            $inserted['products']++;

            /* Insert description */
            $insDesc->execute([$productId, $name]);
            if ($insDesc->rowCount()) $inserted['descriptions']++;

            /* Insert SKU */
            $insSku->execute([$productId, $imagesJson, $sku, $price, $price, $qty]);
            if ($insSku->rowCount()) $inserted['skus']++;

            /* Insert category */
            $catId = (int)($g['goods_mall_category'] ?? 0);
            if ($catId && isset($validCatSet[$catId])) {
                $insCat->execute([$productId, $catId]);
                if ($insCat->rowCount()) $inserted['categories']++;
            }

        } catch (Exception $e) {
            $errors[] = "goods_id {$g['goods_id']}: " . $e->getMessage();
        }
    }

    $out .= "  products: +{$inserted['products']}\n";
    $out .= "  descriptions: +{$inserted['descriptions']}\n";
    $out .= "  skus: +{$inserted['skus']}\n";
    $out .= "  categories: +{$inserted['categories']}\n";
    if ($errors) {
        $out .= "  ERRORS (" . count($errors) . "): " . implode('; ', array_slice($errors, 0, 5)) . "\n";
    }
    return $out;
}

function clearLaravelCache(string $root): void {
    $dirs = [
        "$root/storage/framework/cache/data",
        "$root/storage/framework/views",
        "$root/storage/framework/sessions",
        "$root/bootstrap/cache",
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) continue;
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iter as $file) {
            if ($file->isFile()) @unlink($file->getPathname());
        }
    }
}
