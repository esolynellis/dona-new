<?php
set_exception_handler(null); restore_exception_handler();
ob_start();
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$env  = [];
foreach (file("$root/.env") as $ln) {
    $ln = trim($ln);
    if (!$ln || $ln[0] === '#' || !str_contains($ln, '=')) continue;
    [$k, $v] = explode('=', $ln, 2);
    $env[trim($k)] = trim($v, '"\'');
}
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

// CSS fix for sidebar scroll overlap issue
// When scrollToFixed makes sidebar position:fixed, it can overlap products
// Fix: add max-height + overflow-y:auto so sidebar scrolls internally
$cssTag = '<style id="dona-sidebar-fix">
.page-categories .left-column .x-fixed-top,
.page-list .left-column .x-fixed-top {
  max-height: calc(100vh - 130px);
  overflow-y: auto;
  overflow-x: hidden;
}
/* Hide scrollbar visually but keep functionality */
.page-categories .left-column .x-fixed-top::-webkit-scrollbar,
.page-list .left-column .x-fixed-top::-webkit-scrollbar {
  width: 3px;
}
.page-categories .left-column .x-fixed-top::-webkit-scrollbar-thumb,
.page-list .left-column .x-fixed-top::-webkit-scrollbar-thumb {
  background: #ddd;
  border-radius: 3px;
}
</style>';

$row = $pdo->query("SELECT value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$current = $row['value'] ?? '';

if (strpos($current, 'dona-sidebar-fix') !== false) {
    // Remove old version first
    $current = preg_replace('/<style id="dona-sidebar-fix">.*?<\/style>/s', '', $current);
    $msg = 'updated';
} else {
    $msg = 'injected new';
}

$new = trim($current) . "\n" . $cssTag;

$stmt = $pdo->prepare("UPDATE settings SET value=? WHERE space='base' AND name='head_code'");
$stmt->execute([$new]);

ob_clean();
header('Content-Type: application/json');
echo json_encode([
    'status' => $msg,
    'affected_rows' => $stmt->rowCount(),
    'css_added' => 'max-height:calc(100vh-130px) + overflow-y:auto on .x-fixed-top inside left-column',
], JSON_PRETTY_PRINT);
