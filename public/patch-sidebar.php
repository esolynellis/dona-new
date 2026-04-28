<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$target = "$root/beike/Admin/View/Components/Sidebar.php";

$content = file_get_contents($target);

$old = "            ['route' => 'design_app_home.index', 'prefixes' => ['design_app_home'], 'blank' => false, 'hide_mobile' => true],\n        ];";
$new = "            ['route' => 'design_app_home.index', 'prefixes' => ['design_app_home'], 'blank' => false, 'hide_mobile' => true],\n            ['route' => 'theme.index', 'url' => '/app-preview.php?key=dona2025', 'prefixes' => [], 'blank' => true, 'title' => '📱 App Preview', 'hide_mobile' => true],\n        ];";

if (strpos($content, 'App Preview') !== false) {
    echo json_encode(['status' => 'already patched']);
    exit;
}

@unlink($target);
file_put_contents($target, str_replace($old, $new, $content));

foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

echo json_encode(['status' => 'done']);
