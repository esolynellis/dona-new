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

// 1. Зургийн хэмжээ
$imgStyle = '<style id="dona-img2">
.product-wrap .image .image-old{height:400px!important;max-height:400px!important;min-height:400px!important;width:100%!important;overflow:hidden!important;display:block!important;}
.product-wrap .image .image-old a{display:block!important;height:400px!important;overflow:hidden!important;}
.product-wrap .image .image-old img,.product-wrap .image .image-old .lazyload,.product-wrap .image .image-old .lazyloaded{width:100%!important;height:400px!important;max-height:400px!important;object-fit:cover!important;object-position:center center!important;}
@media(max-width:992px){
  .product-wrap .image .image-old,.product-wrap .image .image-old a{height:300px!important;max-height:300px!important;min-height:300px!important;}
  .product-wrap .image .image-old img,.product-wrap .image .image-old .lazyload,.product-wrap .image .image-old .lazyloaded{height:300px!important;max-height:300px!important;}
}
@media(max-width:576px){
  .product-wrap .image .image-old{height:160px!important;max-height:160px!important;min-height:160px!important;aspect-ratio:unset!important;}
  .product-wrap .image .image-old a{height:160px!important;display:block!important;}
  .product-wrap .image .image-old img,.product-wrap .image .image-old .lazyload,.product-wrap .image .image-old .lazyloaded{width:100%!important;height:160px!important;max-height:160px!important;object-fit:cover!important;}
}
</style>';

// 2. Товчны зай арилгах
$cartStyle = '<style id="dona-cart-fix">
.product-wrap .image .button-wrap{bottom:0!important;opacity:1!important;visibility:visible!important;transform:none!important;display:flex!important;gap:0!important;}
@media(max-width:992px){
  .product-wrap .image .button-wrap{display:flex!important;bottom:0!important;opacity:1!important;gap:0!important;}
  .product-wrap .image .button-wrap .btn-add-cart{font-size:12px!important;padding:7px 6px!important;min-height:34px!important;}
  .product-wrap .image .button-wrap .btn-quick-view,.product-wrap .image .button-wrap .btn-wish{flex:0 0 34px!important;min-height:34px!important;font-size:12px!important;}
}
@media(max-width:480px){
  .product-wrap .image .button-wrap{display:flex!important;bottom:0!important;opacity:1!important;gap:0!important;height:28px!important;}
  .product-wrap .image .button-wrap .btn-add-cart{font-size:11px!important;padding:2px 4px!important;min-height:28px!important;height:28px!important;white-space:nowrap!important;display:flex!important;align-items:center!important;justify-content:center!important;line-height:1!important;border-right:1px solid #333!important;}
  .product-wrap .image .button-wrap .btn-add-cart i,.product-wrap .image .button-wrap .btn-add-cart svg,.product-wrap .image .button-wrap .btn-add-cart .iconfont{display:none!important;}
  .product-wrap .image .button-wrap .btn-quick-view{display:none!important;}
  .product-wrap .image .button-wrap .btn-wish{flex:0 0 28px!important;min-height:28px!important;height:28px!important;padding:0!important;font-size:13px!important;margin:0!important;}
}
</style>';

$row = $pdo->query("SELECT id, value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$current = $row['value'];
$current = preg_replace('/<style id="dona-img2">[\s\S]*?<\/style>\n?/', '', $current);
$current = preg_replace('/<style id="dona-cart-fix">[\s\S]*?<\/style>\n?/', '', $current);
$newValue = trim($current) . "\n" . $imgStyle . "\n" . $cartStyle;
$pdo->prepare("UPDATE settings SET value=? WHERE id=?")->execute([$newValue, $row['id']]);

foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

echo json_encode(['done'=>true,'mobile_img'=>'160px','gap'=>'removed','ts'=>time()]);
