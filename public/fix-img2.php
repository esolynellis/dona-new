<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$env = [];
foreach (file("$root/.env") as $line) {
    $line = trim($line);
    if (!$line || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, '"\'');
}
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD']
);

$style = '<style id="dona-img2">
.product-wrap .image .image-old{height:100px!important;max-height:100px!important;min-height:100px!important;width:100%!important;overflow:hidden!important;display:block!important;}
.product-wrap .image .image-old a{display:block!important;height:100px!important;overflow:hidden!important;}
.product-wrap .image .image-old img,.product-wrap .image .image-old .lazyload,.product-wrap .image .image-old .lazyloaded{width:100%!important;height:100px!important;max-height:100px!important;object-fit:cover!important;object-position:center top!important;}
</style>';

$row = $pdo->query("SELECT id, value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$current = preg_replace('/<style id="dona-img2">[\s\S]*?<\/style>\n?/', '', $row['value']);
$current = preg_replace('/<script id="dona-image-fix-js">[\s\S]*?<\/script>\n?/', '', $current);
$current = preg_replace('/<style id="dona-image-fix">[\s\S]*?<\/style>\n?/', '', $current);
$newValue = trim($current) . "\n" . $style;
$pdo->prepare("UPDATE settings SET value=? WHERE id=?")->execute([$newValue, $row['id']]);

foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

echo json_encode(['done'=>true,'style'=>'injected .image-old 100px'], JSON_PRETTY_PRINT);
