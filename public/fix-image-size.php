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

$imageStyle = '<style id="dona-image-fix">
/* ── Бүх бараа зургийг адилхан хэмжээнд оруулах ── */

/* Зургийн савны хэмжээ тогтмол болгох */
.product-wrap .image,
.goods-item .image,
.product-item .image {
  width: 100% !important;
  padding-top: 100% !important; /* 1:1 aspect ratio */
  position: relative !important;
  overflow: hidden !important;
  background: #f8f8f8 !important;
}

/* Зургийг савны дотор дүүргэж тавих */
.product-wrap .image img,
.product-wrap .image .lazyload,
.product-wrap .image .lazyloaded,
.goods-item .image img,
.product-item .image img {
  position: absolute !important;
  top: 0 !important;
  left: 0 !important;
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
  object-position: center !important;
}

/* Button-wrap position засах (absolute positioning дотор) */
.product-wrap .image .button-wrap {
  position: absolute !important;
  bottom: 0 !important;
  left: 0 !important;
  right: 0 !important;
  width: 100% !important;
  z-index: 10 !important;
}

/* List view дэх зурагнууд */
.product-list-wrap .product-wrap .image {
  width: 220px !important;
  padding-top: 0 !important;
  height: 220px !important;
  flex-shrink: 0 !important;
}
.product-list-wrap .product-wrap .image img {
  position: static !important;
  width: 220px !important;
  height: 220px !important;
  object-fit: cover !important;
}

/* Mobile */
@media (max-width: 576px) {
  .product-wrap .image {
    padding-top: 100% !important;
  }
}
</style>';

$row = $pdo->query("SELECT id, value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Remove old image fix if any
$current = preg_replace('/<style id="dona-image-fix">[\s\S]*?<\/style>\n?/', '', $row['value']);
$newValue = trim($current) . "\n" . $imageStyle;

$pdo->prepare("UPDATE settings SET value=? WHERE id=?")->execute([$newValue, $row['id']]);

// Clear caches
$cleared = 0;
foreach (glob("$root/storage/framework/views/*.php") as $f) { if(@unlink($f)) $cleared++; }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode(['done' => true, 'cache_cleared' => $cleared], JSON_PRETTY_PRINT);
