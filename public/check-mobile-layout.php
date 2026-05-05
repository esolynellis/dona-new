<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$results = [];

// Read master.blade.php to see if head_code is injected
$master = file_get_contents("$root/themes/default/layout/master.blade.php");
$results['master_has_head_code'] = strpos($master, 'head_code') !== false;
$results['master_has_smooth']    = strpos($master, 'smooth.css') !== false;

// Find ALL layout/master blade files across themes
function findAll($dir, $name, $root) {
    $out = [];
    $files = @scandir($dir);
    if (!$files) return $out;
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = "$dir/$f";
        if (is_dir($path)) {
            foreach (findAll($path, $name, $root) as $r) $out[] = $r;
        } elseif (str_contains($f, $name)) {
            $out[] = str_replace("$root/", '', $path);
        }
    }
    return $out;
}

$results['all_master_blades'] = findAll("$root/themes", 'master', $root);
$results['all_mobile_blades'] = findAll("$root/themes", 'mobile', $root);
$results['all_app_blades']    = findAll("$root/themes", 'app', $root);

// Check what head_code is set to in the DB
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
$row = $pdo->query("SELECT value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$results['head_code_value'] = $row ? $row['value'] : 'NOT FOUND';

// Read master.blade.php to check where head_code is injected
preg_match('/<head.*?>(.*?)<\/head>/si', $master, $m);
$results['master_head_section'] = $m[1] ?? substr($master, 0, 1500);

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
