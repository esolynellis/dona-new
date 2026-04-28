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

// 1. Patch config.json
$configPath = "$root/plugins/LOffline/config.json";
$config = json_decode(file_get_contents($configPath), true);
$config['name'] = [
    'mn'    => 'Банкны шилжүүлэг',
    'zh_cn' => 'Банкны шилжүүлэг',
    'en'    => 'Банкны шилжүүлэг',
    'ru'    => 'Банкны шилжүүлэг',
];
@unlink($configPath);
file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
$results['config_updated'] = true;

// 2. Make sure bank info is set correctly
$bankInfo = "Банкны данс: MN720005005129104667\nЗахиалга өгсний дараа дансанд мөнгө шилжүүлж, гүйлгээний баримтыг илгээнэ үү.";
foreach (['mn','zh_cn','en','ru'] as $locale) {
    $check = $pdo->prepare("SELECT id FROM offline_payment_config_descriptions WHERE locale=? LIMIT 1");
    $check->execute([$locale]);
    $row = $check->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $pdo->prepare("UPDATE offline_payment_config_descriptions SET content=? WHERE id=?")->execute([$bankInfo, $row['id']]);
    } else {
        $pdo->prepare("INSERT INTO offline_payment_config_descriptions (locale,content) VALUES (?,?)")->execute([$locale, $bankInfo]);
    }
    $results['content_'.$locale] = 'ok';
}

// 3. Clear cache
foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
