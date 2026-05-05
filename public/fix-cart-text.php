<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$results = [];

// 1. Хэл файлуудад хайх
function searchInDir($dir, $keyword, $root) {
    $out = [];
    $files = @scandir($dir);
    if (!$files) return $out;
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = "$dir/$f";
        if (is_dir($path)) {
            foreach (searchInDir($path, $keyword, $root) as $r) $out[] = $r;
        } elseif (str_ends_with($f, '.php') || str_ends_with($f, '.json')) {
            $c = @file_get_contents($path);
            if ($c && mb_stripos($c, $keyword) !== false) {
                $pos = mb_stripos($c, $keyword);
                $out[] = [
                    'file' => str_replace("$root/", '', $path),
                    'snippet' => mb_substr($c, max(0, $pos-50), 150)
                ];
            }
        }
    }
    return $out;
}

$results['lang_files'] = searchInDir("$root/lang", 'савт', $root);
$results['lang_files2'] = searchInDir("$root/resources/lang", 'савт', $root);
$results['theme_files'] = searchInDir("$root/themes/default", 'савт', $root);

// 2. DB-д хайх
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
$rows = $pdo->query("SELECT id, `group`, `key`, `text` FROM translations WHERE text LIKE '%савт%' OR text LIKE '%Савт%' LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
$results['db_translations'] = $rows;

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
