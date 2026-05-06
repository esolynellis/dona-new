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

// Fix:
// scrollToFixed makes sidebar position:fixed which causes it to overlap products.
// Solution: remove x-fixed-top class from left column BEFORE scrollToFixed runs,
// then apply CSS position:sticky instead (stays in normal flow, no overlap).

$newTag = '<style id="dona-sidebar-fix">
.dona-sticky-sidebar {
  position: sticky !important;
  top: 80px;
  max-height: calc(100vh - 100px);
  overflow-y: auto;
  overflow-x: hidden;
  z-index: 10;
}
.dona-sticky-sidebar::-webkit-scrollbar { width: 3px; }
.dona-sticky-sidebar::-webkit-scrollbar-thumb { background: #ddd; border-radius: 3px; }
</style>
<script id="dona-sidebar-js">
// Remove x-fixed-top from left column before scrollToFixed runs (capture phase)
document.addEventListener("DOMContentLoaded", function() {
  var els = document.querySelectorAll(".left-column .x-fixed-top");
  for (var i = 0; i < els.length; i++) {
    els[i].classList.remove("x-fixed-top");
    els[i].classList.add("dona-sticky-sidebar");
  }
}, true);
</script>';

$row = $pdo->query("SELECT value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$current = $row['value'] ?? '';

// Remove previous versions
$current = preg_replace('/<style id="dona-sidebar-fix">.*?<\/style>/s', '', $current);
$current = preg_replace('/<script id="dona-sidebar-js">.*?<\/script>/s', '', $current);

$new = trim($current) . "\n" . $newTag;

$stmt = $pdo->prepare("UPDATE settings SET value=? WHERE space='base' AND name='head_code'");
$stmt->execute([$new]);

ob_clean();
header('Content-Type: application/json');
echo json_encode([
    'status' => 'updated',
    'fix' => 'removes x-fixed-top class → applies CSS sticky instead (no layout shift, no overlap)',
    'affected_rows' => $stmt->rowCount(),
], JSON_PRETTY_PRINT);
