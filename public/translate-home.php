<?php
/**
 * Translate home screen products to Mongolian.
 * Usage: https://dona-trade.com/translate-home.php?key=dona2025
 */

if (($_GET['key'] ?? '') !== 'dona2025') {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

set_time_limit(300);

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$current = system_setting('base.app_home_setting');
$modules = $current['modules'] ?? [];

$homeIds = [];
foreach ($modules as $mod) {
    if ($mod['code'] === 'product') {
        foreach ($mod['content']['products'] ?? [] as $p) {
            $homeIds[] = $p['id'];
        }
    }
}
$homeIds = array_unique($homeIds);

if (empty($homeIds)) {
    die(json_encode(['error' => 'No home products found']));
}

// Check which ones need translation
$notTranslated = DB::table('product_descriptions')
    ->whereIn('product_id', $homeIds)
    ->where('locale', 'mn')
    ->where(function($q) {
        $q->where('name', '')->orWhereNull('name');
    })
    ->pluck('product_id')
    ->toArray();

$alreadyDone = array_diff($homeIds, $notTranslated);

header('Content-Type: application/json');

if (empty($notTranslated)) {
    echo json_encode([
        'status'  => 'already_translated',
        'message' => 'Бүх бараа орчуулагдсан байна',
        'ids'     => $alreadyDone,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Run translation via artisan command for these specific products
$ids = implode(',', $notTranslated);
$output = shell_exec("cd /www/wwwroot/dona-new && php artisan products:translate-mn --product-ids={$ids} 2>&1");

echo json_encode([
    'status'          => 'done',
    'translated_ids'  => $notTranslated,
    'already_done'    => $alreadyDone,
    'output'          => $output,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
