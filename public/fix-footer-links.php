<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$row = DB::table('settings')
    ->where('code', 'base')
    ->where('name', 'footer_setting')
    ->first();

if (!$row) {
    echo json_encode(['error' => 'footer_setting not found in settings table']);
    exit;
}

$data = json_decode($row->value, true);

// Walk through link1/link2/link3 and replace gitbook URLs
$replaced = 0;
foreach (['link1','link2','link3'] as $linkKey) {
    if (!isset($data['content'][$linkKey]['links'])) continue;
    foreach ($data['content'][$linkKey]['links'] as &$item) {
        if (isset($item['link']) && strpos($item['link'], 'gitbook.io') !== false) {
            $item['link'] = '/app/';
            $replaced++;
        }
    }
    unset($item);
}

DB::table('settings')
    ->where('code', 'base')
    ->where('name', 'footer_setting')
    ->update(['value' => json_encode($data, JSON_UNESCAPED_UNICODE)]);

// Clear view cache
foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode(['replaced' => $replaced, 'done' => true], JSON_PRETTY_PRINT);
