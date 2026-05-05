<?php
/**
 * Brand data API - returns {product_id: brand_name} for given IDs
 * Used by brand-group.js to apply grouping without server-side blade changes
 */
header('Content-Type: application/json');
header('Cache-Control: public, max-age=300');
header('Access-Control-Allow-Origin: *');

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
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT]
    );
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (Exception $e) {
    echo json_encode([]);
    exit;
}

// Mode 1: Get brands for specific product IDs
if (!empty($_GET['ids'])) {
    $rawIds = $_GET['ids'];
    // Allow only integers separated by commas
    $ids = array_filter(array_map('intval', explode(',', $rawIds)));
    if (empty($ids)) {
        echo json_encode([]);
        exit;
    }
    $ids = array_slice($ids, 0, 100); // max 100 IDs
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        SELECT id, brand_name
        FROM products
        WHERE id IN ($placeholders)
          AND brand_name IS NOT NULL
          AND brand_name != ''
          AND active = 1
    ");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map  = [];
    foreach ($rows as $r) {
        $map[$r['id']] = $r['brand_name'];
    }
    echo json_encode($map);
    exit;
}

// Mode 2: Get all active products for a specific brand (for "more from brand" feature)
if (!empty($_GET['brand'])) {
    $brand = $_GET['brand'];
    $exclude = (int)($_GET['exclude'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT p.id, pd.name, p.images, ps.price
        FROM products p
        LEFT JOIN product_descriptions pd ON pd.product_id = p.id AND pd.locale = 'mn'
        LEFT JOIN product_skus ps ON ps.product_id = p.id AND ps.is_default = 1
        WHERE p.brand_name = ?
          AND p.active = 1
          AND p.deleted_at IS NULL
          AND p.id != ?
        ORDER BY p.position ASC, p.id ASC
        LIMIT 8
    ");
    $stmt->execute([$brand, $exclude]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out  = [];
    foreach ($rows as $r) {
        $images = json_decode($r['images'] ?? '[]', true);
        $img    = ($images[0] ?? '') . '?imageView2/2/w/400/h/400';
        $out[] = [
            'id'    => $r['id'],
            'name'  => $r['name'] ?? $r['id'],
            'url'   => '/products/' . $r['id'],
            'image' => $img,
            'price' => $r['price'] ?? 0,
        ];
    }
    echo json_encode($out);
    exit;
}

echo json_encode([]);
