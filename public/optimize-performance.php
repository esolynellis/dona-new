<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$results = [];

function pw($path, $content) {
    @unlink($path);
    return file_put_contents($path, $content) !== false;
}

// ═══════════════════════════════════════
// 1. ProductRepo — Cache similar products
// ═══════════════════════════════════════
$repoPath = "$root/beike/Repositories/ProductRepo.php";
$repo = file_get_contents($repoPath);

if (strpos($repo, 'use Illuminate\Support\Facades\Cache;') === false) {
    $repo = str_replace(
        'use Illuminate\Http\Resources\Json\AnonymousResourceCollection;',
        'use Illuminate\Http\Resources\Json\AnonymousResourceCollection;' . "\n" . 'use Illuminate\Support\Facades\Cache;',
        $repo
    );
}

$oldStart = '    public static function getSimilarProducts(Product $product, int $limit = 12): AnonymousResourceCollection
    {
        $categoryIds = $product->categories()->pluck(\'categories.id\')->toArray();';

$newStart = '    public static function getSimilarProducts(Product $product, int $limit = 12): AnonymousResourceCollection
    {
        $cacheKey = "similar_{$product->id}_" . locale() . "_{$limit}";
        if (Cache::has($cacheKey)) {
            $data = Cache::get($cacheKey);
            return ProductSimple::collection(collect($data));
        }

        $categoryIds = $product->categories()->pluck(\'categories.id\')->toArray();';

$oldEnd = '        return ProductSimple::collection($products);
    }

    /**
     * 获取商品筛选对象';

$newEnd = '        Cache::put($cacheKey, $products, now()->addMinutes(15));
        return ProductSimple::collection($products);
    }

    /**
     * 获取商品筛选对象';

if (strpos($repo, $oldStart) !== false && strpos($repo, 'Cache::has($cacheKey)') === false) {
    $repo = str_replace($oldStart, $newStart, $repo);
    $repo = str_replace($oldEnd, $newEnd, $repo);
    pw($repoPath, $repo);
    $results['1_similar_cache'] = 'OK';
} else {
    $results['1_similar_cache'] = 'skip';
}

// ═══════════════════════════════════════
// 2. ProductDetail — similar = [] (lazy loaded by JS)
// ═══════════════════════════════════════
$detailPath = "$root/beike/Shop/Http/Resources/ProductDetail.php";
$detail = file_get_contents($detailPath);

if (strpos($detail, 'ProductRepo::getSimilarProducts') !== false) {
    $detail = preg_replace(
        "/'similar'\s*=>\s*ProductSimple::collection\(\s*ProductRepo::getSimilarProducts\([^)]+\)\s*\)->jsonSerialize\(\),/",
        "'similar' => [],",
        $detail
    );
    pw($detailPath, $detail);
    $results['2_product_detail'] = 'similar removed from main response';
} else {
    $results['2_product_detail'] = 'skip';
}

// ═══════════════════════════════════════
// 3. ProductController — add similar() method
// ═══════════════════════════════════════
$ctrlPath = "$root/beike/ShopAPI/Controllers/ProductController.php";
$ctrl = file_get_contents($ctrlPath);

if (strpos($ctrl, 'function similar') === false) {
    $ctrl = str_replace(
        "    public function show(Request \$request, Product \$product): ProductDetail\n    {\n        return new ProductDetail(\$product);\n    }\n}",
        "    public function show(Request \$request, Product \$product): ProductDetail\n    {\n        return new ProductDetail(\$product);\n    }\n\n    public function similar(Request \$request, Product \$product): AnonymousResourceCollection\n    {\n        return ProductRepo::getSimilarProducts(\$product, (int)\$request->get('limit', 8));\n    }\n}",
        $ctrl
    );
    pw($ctrlPath, $ctrl);
    $results['3_similar_endpoint'] = 'OK';
} else {
    $results['3_similar_endpoint'] = 'skip';
}

// ═══════════════════════════════════════
// 4. API route — /products/{id}/similar
// ═══════════════════════════════════════
$routePath = "$root/beike/ShopAPI/Routes/api.php";
$routes = file_get_contents($routePath);

if (strpos($routes, 'products/{product}/similar') === false) {
    $routes = str_replace(
        "Route::get('products/{product}', [ShopController\\ProductController::class, 'show']);",
        "Route::get('products/{product}', [ShopController\\ProductController::class, 'show']);\n    Route::get('products/{product}/similar', [ShopController\\ProductController::class, 'similar']);",
        $routes
    );
    pw($routePath, $routes);
    $results['4_api_route'] = 'OK';
} else {
    $results['4_api_route'] = 'skip';
}

// ═══════════════════════════════════════
// 5. HomeController — 5 min cache
// ═══════════════════════════════════════
$homePath = "$root/beike/ShopAPI/Controllers/HomeController.php";
$home = file_get_contents($homePath);

if (strpos($home, 'Cache::remember') === false) {
    if (strpos($home, 'use Illuminate\Support\Facades\Cache;') === false) {
        $home = str_replace(
            'use Illuminate\Http\JsonResponse;',
            "use Illuminate\Http\JsonResponse;\nuse Illuminate\Support\Facades\Cache;",
            $home
        );
    }
    $home = str_replace(
        'public function index(): JsonResponse
    {',
        'public function index(): JsonResponse
    {
        $cacheKey = \'app_home_\' . locale();
        $moduleItems = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            return $this->loadModules();
        });
        return json_success(trans(\'common.get_success\'), $moduleItems);
    }

    private function loadModules(): array
    {',
        $home
    );
    $home = str_replace(
        "        return json_success(trans('common.get_success'), \$moduleItems);\n    }",
        "        return \$moduleItems;\n    }",
        $home
    );
    pw($homePath, $home);
    $results['5_home_cache'] = 'OK';
} else {
    $results['5_home_cache'] = 'skip';
}

// ═══════════════════════════════════════
// 6. Clear all caches
// ═══════════════════════════════════════
$cleared = 0;
foreach (glob("$root/storage/framework/views/*.php") as $f) { if(@unlink($f)) $cleared++; }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }
foreach (glob("$root/storage/framework/cache/data/*/*") as $f) { if(is_file($f)) @unlink($f); }
$results['6_cache_cleared'] = $cleared . ' view files';

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
