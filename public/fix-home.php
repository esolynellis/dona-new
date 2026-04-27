<?php
/**
 * One-time fix: replace soft-deleted home screen products with active ones.
 * Delete this file after use.
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Beike\Repositories\SettingRepo;
use Illuminate\Support\Facades\DB;

$current = system_setting('base.app_home_setting');
$modules = $current['modules'] ?? [];
$report  = [];

$moduleIndex = 0;
foreach ($modules as &$mod) {
    if ($mod['code'] !== 'product') {
        $moduleIndex++;
        continue;
    }

    $existingIds = collect($mod['content']['products'] ?? [])->pluck('id')->toArray();

    $newProducts = DB::table('products as p')
        ->join('product_descriptions as pd', 'p.id', '=', 'pd.product_id')
        ->whereNull('p.deleted_at')
        ->where('p.active', 1)
        ->where('pd.locale', 'mn')
        ->where('pd.name', '!=', '')
        ->whereNotNull('p.images')
        ->where('p.images', '!=', '[]')
        ->whereNotIn('p.id', $existingIds)
        ->orderByRaw('RAND()')
        ->limit(8)
        ->get(['p.id', 'pd.name', 'p.images']);

    $entries = [];
    foreach ($newProducts as $row) {
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

    $mod['content']['products'] = $entries;
    $report["module_{$moduleIndex}"] = count($entries) . ' products set: ' . implode(', ', array_column($entries, 'id'));
    $moduleIndex++;
}
unset($mod);

$current['modules'] = $modules;
SettingRepo::storeValue('app_home_setting', $current);

header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'report' => $report], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
