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

$style = '<style id="dona-cart-fix">
/* Desktop: үргэлж харагдана */
.product-wrap .image .button-wrap{bottom:0!important;opacity:1!important;visibility:visible!important;transform:none!important;display:flex!important;}

/* Tablet */
@media(max-width:992px){
  .product-wrap .image .button-wrap{display:flex!important;bottom:0!important;opacity:1!important;}
  .product-wrap .image .button-wrap .btn-add-cart{font-size:12px!important;padding:7px 6px!important;min-height:34px!important;}
  .product-wrap .image .button-wrap .btn-quick-view,.product-wrap .image .button-wrap .btn-wish{flex:0 0 34px!important;min-height:34px!important;font-size:12px!important;}
}

/* Mobile жижиг: зөвхөн icon, текст нуух */
@media(max-width:480px){
  .product-wrap .image .button-wrap{display:flex!important;bottom:0!important;opacity:1!important;}
  /* Саглагт нэмэх текстийг нуух, icon л үлдээх */
  .product-wrap .image .button-wrap .btn-add-cart{
    font-size:0!important;
    padding:0!important;
    min-height:32px!important;
    display:flex!important;
    align-items:center!important;
    justify-content:center!important;
  }
  .product-wrap .image .button-wrap .btn-add-cart i,
  .product-wrap .image .button-wrap .btn-add-cart svg,
  .product-wrap .image .button-wrap .btn-add-cart .iconfont{
    font-size:16px!important;
    display:block!important;
  }
  .product-wrap .image .button-wrap .btn-quick-view,
  .product-wrap .image .button-wrap .btn-wish{
    flex:0 0 32px!important;
    min-height:32px!important;
    padding:0!important;
    font-size:14px!important;
  }
}
</style>';

$row = $pdo->query("SELECT id, value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$current = preg_replace('/<style id="dona-cart-fix">[\s\S]*?<\/style>\n?/', '', $row['value']);
$newValue = trim($current) . "\n" . $style;
$pdo->prepare("UPDATE settings SET value=? WHERE id=?")->execute([$newValue, $row['id']]);

foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

echo json_encode(['done'=>true,'fix'=>'mobile cart button icon-only on small screens','ts'=>time()]);
