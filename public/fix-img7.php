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
.product-wrap .image .image-old{height:400px!important;max-height:400px!important;min-height:400px!important;width:100%!important;overflow:hidden!important;display:block!important;}
.product-wrap .image .image-old a{display:block!important;height:400px!important;overflow:hidden!important;}
.product-wrap .image .image-old img,.product-wrap .image .image-old .lazyload,.product-wrap .image .image-old .lazyloaded{width:100%!important;height:400px!important;max-height:400px!important;object-fit:cover!important;object-position:center center!important;}
@media(max-width:992px){
  .product-wrap .image .image-old,.product-wrap .image .image-old a{height:300px!important;max-height:300px!important;min-height:300px!important;}
  .product-wrap .image .image-old img,.product-wrap .image .image-old .lazyload,.product-wrap .image .image-old .lazyloaded{height:300px!important;max-height:300px!important;}
}
@media(max-width:576px){
  .product-wrap .image .image-old,.product-wrap .image .image-old a{height:200px!important;max-height:200px!important;min-height:200px!important;}
  .product-wrap .image .image-old img,.product-wrap .image .image-old .lazyload,.product-wrap .image .image-old .lazyloaded{height:200px!important;max-height:200px!important;}
}
</style>';

$row = $pdo->query("SELECT id, value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$current = preg_replace('/<style id="dona-img2">[\s\S]*?<\/style>\n?/', '', $row['value']);
$newValue = trim($current) . "\n" . $style;
$pdo->prepare("UPDATE settings SET value=? WHERE id=?")->execute([$newValue, $row['id']]);

foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

echo json_encode(['done'=>true,'desktop'=>'400px','tablet'=>'300px','mobile'=>'200px','ts'=>time()]);
