<?php
/**
 * Refresh home screen: replace all product modules with the newest active products.
 * Usage: https://dona-trade.com/refresh-home.php?key=dona2025
 */

if (($_GET['key'] ?? '') !== 'dona2025') {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Beike\Repositories\SettingRepo;
use Illuminate\Support\Facades\DB;

$current = system_setting('base.app_home_setting');
$modules = $current['modules'] ?? [];
$report  = [];

// Get newest active products with Mongolian names
$allNewProducts = DB::table('products as p')
    ->join('product_descriptions as pd', 'p.id', '=', 'pd.product_id')
    ->whereNull('p.deleted_at')
    ->where('p.active', 1)
    ->where('pd.locale', 'mn')
    ->where('pd.name', '!=', '')
    ->whereNotNull('p.images')
    ->where('p.images', '!=', '[]')
    ->orderBy('p.created_at', 'desc')
    ->limit(100)
    ->get(['p.id', 'pd.name', 'p.images', 'p.created_at']);

$productPool = $allNewProducts->toArray();
$offset = 0;

foreach ($modules as $i => &$mod) {
    if ($mod['code'] !== 'product') continue;

    $slice = array_slice($productPool, $offset, 8);
    $offset += 8;

    $entries = [];
    foreach ($slice as $row) {
        $images    = json_decode($row->images, true) ?? [];
        $image     = $images[0] ?? '';
        $entries[] = [
            'id'           => $row->id,
            'name'         => $row->name,
            'image'        => $image,
            'image_format' => $image,
            'status'       => true,
        ];
    }

    $modules[$i]['content']['products'] = $entries;
    $report["module_{$i}"] = count($entries) . ' products | ids: ' . implode(', ', array_column($entries, 'id'));
}
unset($mod);

$current['modules'] = $modules;
SettingRepo::storeValue('app_home_setting', $current);

header('Content-Type: application/json');
echo json_encode([
    'status'       => 'ok',
    'total_pool'   => count($productPool),
    'report'       => $report,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
