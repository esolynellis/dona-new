<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';

// Find product card related CSS and blade files
$results = [];

// Read main shop CSS
$appCss = "$root/public/build/beike/shop/default/css/app.css";
$css = @file_get_contents($appCss);
if ($css) {
    // Find hover-related cart button rules
    preg_match_all('/[^{}]*cart[^{}]*\{[^{}]*\}/', $css, $m);
    $results['cart_css_rules'] = array_slice($m[0], 0, 20);

    // Find .product-item hover rules
    preg_match_all('/\.product-item[^{}]*hover[^{}]*\{[^{}]*\}|[^{}]*hover[^{}]*\.product[^{}]*\{[^{}]*\}/', $css, $m2);
    $results['product_hover_rules'] = array_slice($m2[0], 0, 20);

    // Search for opacity:0 or display:none near cart
    preg_match_all('/.{100}(cart|add-cart|add_cart).{0,200}/i', $css, $m3);
    $results['cart_context'] = array_slice($m3[0], 0, 10);
}

// Find product card blade templates
function findBlades($dir, $keyword, $root, $depth = 0) {
    $out = [];
    if ($depth > 5) return $out;
    $files = @scandir($dir);
    if (!$files) return $out;
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = "$dir/$f";
        if (is_dir($path)) {
            foreach (findBlades($path, $keyword, $root, $depth+1) as $r) $out[] = $r;
        } elseif (str_ends_with($f, '.blade.php')) {
            $c = @file_get_contents($path);
            if ($c && stripos($c, $keyword) !== false) {
                $out[] = [
                    'file' => str_replace("$root/", '', $path),
                    'snippet' => substr($c, max(0, stripos($c, $keyword) - 100), 400)
                ];
            }
        }
    }
    return $out;
}

$results['blade_cart'] = findBlades("$root/themes", 'cart', $root);

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
