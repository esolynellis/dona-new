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

// 1. Check all head_code related rows
$rows = $pdo->query("SELECT id, space, name, value FROM settings WHERE name='head_code'")->fetchAll(PDO::FETCH_ASSOC);

// 2. Check what system_setting actually reads — look at config cache
$configCached = @file_get_contents("$root/bootstrap/cache/config.php");

// 3. Check if there's a settings cache in Laravel cache
$cacheFiles = glob("$root/storage/framework/cache/data/*/*");
$settingsCache = [];
foreach ($cacheFiles as $f) {
    $content = @file_get_contents($f);
    if ($content && strpos($content, 'head_code') !== false) {
        $settingsCache[] = ['file' => basename($f), 'content' => substr($content, 0, 500)];
    }
}

// 4. Check master.blade.php compiled cache
$compiledViews = glob("$root/storage/framework/views/*.php");
$masterCompiled = null;
foreach ($compiledViews as $v) {
    $c = @file_get_contents($v);
    if ($c && strpos($c, 'head_code') !== false) {
        $masterCompiled = ['file' => basename($v), 'snippet' => substr($c, strpos($c, 'head_code') - 50, 300)];
        break;
    }
}

// 5. Read the master.blade.php to see exact head_code rendering line
$master = file_get_contents("$root/themes/default/layout/master.blade.php");
preg_match('/(.{0,100}head_code.{0,200})/s', $master, $m);

header('Content-Type: application/json');
echo json_encode([
    'db_head_code_rows'    => $rows,
    'settings_in_cache'    => $settingsCache,
    'compiled_view_found'  => $masterCompiled,
    'master_head_code_line'=> $m[1] ?? 'not found',
    'config_cache_exists'  => $configCached !== false,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
