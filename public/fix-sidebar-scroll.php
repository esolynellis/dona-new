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

$out = [];

// STEP 1: Remove all my sidebar CSS/JS from head_code
$row = $pdo->query("SELECT value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$current = $row['value'] ?? '';
$original = $current;

$current = preg_replace('/<style id="dona-sidebar-fix">.*?<\/style>\s*/s', '', $current);
$current = preg_replace('/<script id="dona-sidebar-js">.*?<\/script>\s*/s', '', $current);
$current = trim($current);

if ($current !== $original) {
    $stmt = $pdo->prepare("UPDATE settings SET value=? WHERE space='base' AND name='head_code'");
    $stmt->execute([$current]);
    $out['head_code'] = 'reverted - removed sidebar CSS/JS';
} else {
    $out['head_code'] = 'nothing to remove';
}

// STEP 2: Patch the built app.js to exclude .left-column .x-fixed-top from scrollToFixed
$jsFile = $root . '/public/build/beike/shop/default/js/app.js';
if (!file_exists($jsFile)) {
    $out['js_patch'] = 'ERROR: file not found: ' . $jsFile;
} else {
    $js = file_get_contents($jsFile);

    // Check if already patched
    if (strpos($js, 'not(".left-column') !== false || strpos($js, "not('.left-column") !== false) {
        $out['js_patch'] = 'already patched';
    } else {
        // Find the scrollToFixed call and change selector to exclude left-column
        // Original: $('.x-fixed-top').scrollToFixed({
        // Patched:  $('.x-fixed-top').not($('.left-column .x-fixed-top')).scrollToFixed({
        $patched = str_replace(
            "$('.x-fixed-top').scrollToFixed({",
            "$('.x-fixed-top').not($('.left-column .x-fixed-top')).scrollToFixed({",
            $js,
            $count
        );
        if ($count === 0) {
            // Try double-quote variant
            $patched = str_replace(
                '$(".x-fixed-top").scrollToFixed({',
                '$(".x-fixed-top").not($(".left-column .x-fixed-top")).scrollToFixed({',
                $js,
                $count
            );
        }
        if ($count > 0) {
            $written = file_put_contents($jsFile, $patched);
            $out['js_patch'] = $written !== false ? "patched $count occurrence(s)" : 'ERROR: write failed';
        } else {
            // Show what's actually in the file around scrollToFixed
            preg_match('/(.{0,80}scrollToFixed.{0,80})/s', $js, $m);
            $out['js_patch'] = 'ERROR: pattern not found. Context: ' . ($m[1] ?? 'not found');
        }
    }
}

ob_clean();
header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
