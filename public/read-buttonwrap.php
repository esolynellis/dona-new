<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$css = file_get_contents("$root/public/build/beike/shop/default/css/app.css");

// Find ALL rules mentioning button-wrap
preg_match_all('/[^}]*button-wrap[^{]*\{[^}]+\}/i', $css, $m);
$results['button_wrap_rules'] = $m[0];

// Find product-wrap image area rules (opacity, transform, hover)
preg_match_all('/\.product-wrap[^}]*\{[^}]+\}/i', $css, $m2);
$results['product_wrap_rules'] = array_slice($m2[0], 0, 30);

// Read shared/product.blade.php fully
$blade = file_get_contents("$root/themes/default/shared/product.blade.php");
$results['shared_product_blade'] = $blade;

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
