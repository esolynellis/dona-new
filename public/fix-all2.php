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

$cartStyle = '<style id="dona-cart-fix">
.product-wrap .image .button-wrap{bottom:0!important;opacity:1!important;visibility:visible!important;transform:none!important;display:flex!important;gap:0!important;padding:0!important;margin:0!important;}
@media(max-width:992px){
  .product-wrap .image .button-wrap{display:flex!important;bottom:0!important;opacity:1!important;gap:0!important;}
  .product-wrap .image .button-wrap .btn-add-cart{font-size:12px!important;padding:7px 6px!important;min-height:34px!important;}
  .product-wrap .image .button-wrap .btn-quick-view,.product-wrap .image .button-wrap .btn-wish{flex:0 0 34px!important;min-height:34px!important;font-size:12px!important;}
}
@media(max-width:480px){
  .product-wrap .image .button-wrap{
    display:flex!important;bottom:0!important;opacity:1!important;
    gap:0!important;padding:0!important;margin:0!important;height:28px!important;
  }
  .product-wrap .image .button-wrap .btn-add-cart{
    font-size:11px!important;
    padding:0 6px!important;
    min-height:28px!important;height:28px!important;
    white-space:nowrap!important;
    display:flex!important;align-items:center!important;justify-content:center!important;
    line-height:1!important;
    border:none!important;
    border-right:none!important;
    margin:0!important;
    flex:1!important;
  }
  .product-wrap .image .button-wrap .btn-add-cart i,
  .product-wrap .image .button-wrap .btn-add-cart svg,
  .product-wrap .image .button-wrap .btn-add-cart .iconfont{display:none!important;}
  .product-wrap .image .button-wrap .btn-quick-view{display:none!important;}
  .product-wrap .image .button-wrap .btn-wish{
    flex:0 0 28px!important;width:28px!important;
    min-height:28px!important;height:28px!important;
    padding:0!important;font-size:13px!important;
    margin:0!important;border-left:1px solid #333!important;
  }
}
</style>';

$row = $pdo->query("SELECT id, value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$current = preg_replace('/<style id="dona-cart-fix">[\s\S]*?<\/style>\n?/', '', $row['value']);
$newValue = trim($current) . "\n" . $cartStyle;
$pdo->prepare("UPDATE settings SET value=? WHERE id=?")->execute([$newValue, $row['id']]);

foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

echo json_encode(['done'=>true,'fix'=>'gap fully removed','ts'=>time()]);
