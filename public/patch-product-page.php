<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$target = "$root/themes/default/product/product.blade.php";

$content = file_get_contents($target);

$results = [];

// 1. Add mobile back button (if not already patched)
if (strpos($content, 'mobile-back-bar') === false) {
    $old = "@section('content')\n  @if (!request('iframe'))\n    <x-shop-breadcrumb type=\"product\" :value=\"\$product['id']\" />\n  @endif";
    $new = "@section('content')\n  @if (!request('iframe'))\n    @if(is_mobile())\n      <div class=\"mobile-back-bar d-lg-none\">\n        <button onclick=\"history.length > 1 ? history.back() : (window.location='/')\" class=\"mobile-back-btn\">\n          <i class=\"bi bi-arrow-left\"></i> Буцах\n        </button>\n      </div>\n    @else\n      <x-shop-breadcrumb type=\"product\" :value=\"\$product['id']\" />\n    @endif\n  @endif";
    $content = str_replace($old, $new, $content);
    $results['back_button'] = 'added';
} else {
    $results['back_button'] = 'already exists';
}

// 2. Add mobile back button styles
if (strpos($content, 'mobile-back-bar') !== false && strpos($content, '.mobile-back-bar') === false) {
    $styleInsert = "  <style>\n    /* ── Mobile back button ── */\n    .mobile-back-bar {\n      position: sticky;\n      top: 0;\n      z-index: 100;\n      background: #fff;\n      padding: 10px 16px;\n      border-bottom: 1px solid #f0f0f0;\n    }\n    .mobile-back-btn {\n      background: none;\n      border: none;\n      font-size: 15px;\n      color: #333;\n      padding: 4px 0;\n      display: flex;\n      align-items: center;\n      gap: 6px;\n    }\n    .mobile-back-btn i { font-size: 18px; }\n    .product-main-img { min-height: 200px; background: #f5f5f5; }\n\n    /* ── Similar & Relations wrapper ── */";
    $content = str_replace("  <style>\n    /* ── Similar & Relations wrapper ── */", $styleInsert, $content);
    $results['styles'] = 'added';
}

// 3. Fix mobile images - lazy load all except first
$oldMobileImg = "<div class=\"swiper-slide d-flex align-items-center justify-content-center\" v-for=\"image, index in images\" :key=\"index\">\n                  <img :src=\"image.preview\" class=\"img-fluid\">\n                </div>";
$newMobileImg = "<div class=\"swiper-slide d-flex align-items-center justify-content-center\" v-for=\"(image, index) in images\" :key=\"index\">\n                  <img v-if=\"index === 0\" :src=\"image.preview\" class=\"img-fluid product-main-img\">\n                  <img v-else :src=\"image.preview\" class=\"img-fluid product-main-img\" loading=\"lazy\">\n                </div>";

if (strpos($content, $oldMobileImg) !== false) {
    $content = str_replace($oldMobileImg, $newMobileImg, $content);
    $results['lazy_images'] = 'added';
} else {
    $results['lazy_images'] = 'already patched or not found';
}

@unlink($target);
$r = file_put_contents($target, $content);
$results['write'] = $r !== false;

foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
