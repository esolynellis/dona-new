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

$style = '<style id="dona-mobile-layout">
@media(max-width:576px){
  /* Хуудас хажуу тийш гүйхээс сэргийлэх */
  html, body { overflow-x:hidden!important; max-width:100vw!important; }
  .container, .container-fluid { overflow-x:hidden!important; padding-left:12px!important; padding-right:12px!important; }
  /* Row flex-wrap зөв болгох */
  .row { flex-wrap:wrap!important; margin-left:0!important; margin-right:0!important; }
  /* Ангиллын мап section */
  .module-item .row, .module-info .row { flex-wrap:wrap!important; overflow-x:hidden!important; }
  /* Модулийн section overflow */
  .modules-box, .module-item, .module-info { overflow-x:hidden!important; max-width:100%!important; }
  /* Swiper дотроо хэт өргөн болохоос сэргийлэх */
  .swiper-wrapper { width:100%!important; }
  /* Col дундаа хуваагдахгүй болох */
  [class*="col-"] { max-width:100%!important; }
}
</style>';

$row = $pdo->query("SELECT id, value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$current = preg_replace('/<style id="dona-mobile-layout">[\s\S]*?<\/style>\n?/', '', $row['value']);
$newValue = trim($current) . "\n" . $style;
$pdo->prepare("UPDATE settings SET value=? WHERE id=?")->execute([$newValue, $row['id']]);

foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

echo json_encode(['done'=>true,'fix'=>'mobile horizontal overflow fixed','ts'=>time()]);
