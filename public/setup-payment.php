<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$env = [];
foreach (file("$root/.env") as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, '"\'');
}
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$results = [];

// Fix plugin name in settings - all locales
foreach (['mn','zh_cn','en','ru'] as $locale) {
    $check = $pdo->prepare("SELECT id FROM settings WHERE space='l_offline' AND name='name_".$locale."' LIMIT 1");
    $check->execute();
    if ($check->fetch()) {
        $pdo->prepare("UPDATE settings SET value='Банкны шилжүүлэг' WHERE space='l_offline' AND name='name_".$locale."'")->execute();
    } else {
        $pdo->prepare("INSERT INTO settings (type,space,name,value,json) VALUES ('plugin','l_offline',?,?,0)")
            ->execute(['name_'.$locale, 'Банкны шилжүүлэг']);
    }
}

// Also update plugins table name if exists
$pdo->query("UPDATE plugins SET name='Банкны шилжүүлэг' WHERE code='l_offline'")->execute();
$results['name_updated'] = true;

// Check what columns plugins table has
$cols = $pdo->query("DESCRIBE plugins")->fetchAll(PDO::FETCH_COLUMN);
$results['plugins_columns'] = $cols;

$pluginRow = $pdo->query("SELECT * FROM plugins WHERE code='l_offline' LIMIT 1")->fetchAll(PDO::FETCH_ASSOC);
$results['l_offline_plugin_row'] = $pluginRow;

foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
