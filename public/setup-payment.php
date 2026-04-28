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

// 1. Enable l_offline plugin
$pdo->prepare("UPDATE settings SET value='1' WHERE space='l_offline' AND name='status'")->execute();
$results['l_offline_enabled'] = true;

// 2. Check offline_payment_config_descriptions
$descs = $pdo->query("SELECT * FROM offline_payment_config_descriptions LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$results['current_descriptions'] = $descs;

// 3. Check plugins table for QPay
$plugins = $pdo->query("SELECT * FROM plugins WHERE code LIKE '%pay%' OR code LIKE '%qpay%'")->fetchAll(PDO::FETCH_ASSOC);
$results['pay_plugins'] = $plugins;

// 4. Check all payment-related settings
$allPaySettings = $pdo->query("SELECT * FROM settings WHERE space LIKE '%pay%' OR space LIKE '%qpay%'")->fetchAll(PDO::FETCH_ASSOC);
$results['all_pay_settings'] = $allPaySettings;

// 5. Set bank account in offline payment descriptions
$locales = ['mn', 'zh_cn', 'en', 'ru'];
$bankInfo = "Банкны данс: MN720005005129104667\nЗахиалга өгсний дараа дансанд мөнгө шилжүүлж, гүйлгээний баримтыг илгээнэ үү.";

foreach ($locales as $locale) {
    $existing = $pdo->prepare("SELECT id FROM offline_payment_config_descriptions WHERE locale=? LIMIT 1");
    $existing->execute([$locale]);
    $row = $existing->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $pdo->prepare("UPDATE offline_payment_config_descriptions SET description=?, name='Банкны шилжүүлэг' WHERE id=?")
            ->execute([$bankInfo, $row['id']]);
        $results['desc_' . $locale] = 'updated';
    } else {
        $pdo->prepare("INSERT INTO offline_payment_config_descriptions (locale, name, description) VALUES (?, 'Банкны шилжүүлэг', ?)")
            ->execute([$locale, $bankInfo]);
        $results['desc_' . $locale] = 'inserted';
    }
}

// Clear cache
foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
