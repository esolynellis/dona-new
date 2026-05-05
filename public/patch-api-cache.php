<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$results = [];

// 1. Create a CacheResponse middleware
$middlewarePath = "$root/beike/Shop/Http/Middleware/CacheResponse.php";
$middlewareContent = '<?php
namespace Beike\Shop\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CacheResponse
{
    /**
     * Cache GET API responses in the browser for fast repeat loads.
     */
    public function handle(Request $request, Closure $next, int $ttl = 60): mixed
    {
        $response = $next($request);

        // Only cache successful GET responses
        if ($request->isMethod(\'GET\') && $response->getStatusCode() === 200) {
            $response->headers->set(\'Cache-Control\', "public, max-age={$ttl}, stale-while-revalidate=" . ($ttl * 2));
            $response->headers->set(\'Vary\', \'Accept-Language, Accept-Encoding\');
        }

        return $response;
    }
}
';

// Check if Shop middleware dir exists
$middlewareDir = "$root/beike/Shop/Http/Middleware";
if (!is_dir($middlewareDir)) {
    @mkdir($middlewareDir, 0755, true);
}

@unlink($middlewarePath);
$wrote = file_put_contents($middlewarePath, $middlewareContent);
$results['1_middleware'] = $wrote !== false ? 'created' : 'failed';

// 2. Register middleware in ShopAPI routes or kernel
// Find the ShopAPI RouteServiceProvider or Kernel
$kernelPath = "$root/beike/ShopAPI/Http/Kernel.php";
$kernel = @file_get_contents($kernelPath);
if ($kernel) {
    $results['kernel_found'] = true;
    $results['kernel_preview'] = substr($kernel, 0, 500);
}

// Try ShopAPI routes file to add middleware
$apiRoutePath = "$root/beike/ShopAPI/Routes/api.php";
$apiRoute = @file_get_contents($apiRoutePath);
if ($apiRoute && strpos($apiRoute, 'CacheResponse') === false) {
    // Wrap the products/{product} show route with cache middleware
    $apiRoute = str_replace(
        "Route::get('products/{product}', [ShopController\\ProductController::class, 'show']);",
        "Route::get('products/{product}', [ShopController\\ProductController::class, 'show'])->middleware(\\Beike\\Shop\\Http\\Middleware\\CacheResponse::class . ':120');",
        $apiRoute
    );
    // Cache home route for 5 min
    $apiRoute = str_replace(
        "Route::get('home', [ShopController\\HomeController::class, 'index']);",
        "Route::get('home', [ShopController\\HomeController::class, 'index'])->middleware(\\Beike\\Shop\\Http\\Middleware\\CacheResponse::class . ':300');",
        $apiRoute
    );
    // Cache categories
    $apiRoute = str_replace(
        "Route::get('categories', [ShopController\\CategoryController::class,",
        "Route::get('categories', [ShopController\\CategoryController::class,",
        $apiRoute
    );
    @unlink($apiRoutePath);
    $wrote2 = file_put_contents($apiRoutePath, $apiRoute);
    $results['2_api_routes'] = $wrote2 !== false ? 'patched' : 'failed';
} else {
    $results['2_api_routes'] = $apiRoute ? 'skip_already_done' : 'route_file_not_found';
}

// 3. Also add font-display: swap to smooth.css so Google Fonts don't block rendering
$smoothCss = @file_get_contents("$root/public/smooth.css");
if ($smoothCss && strpos($smoothCss, 'font-display') === false) {
    $fontFix = "\n/* ── Prevent font flash ── */\n@font-face { font-display: swap; }\n";
    $smoothCss .= $fontFix;
    @unlink("$root/public/smooth.css");
    file_put_contents("$root/public/smooth.css", $smoothCss);
    $results['3_font_display'] = 'added';
} else {
    $results['3_font_display'] = 'skip';
}

// 4. Clear caches
$cleared = 0;
foreach (glob("$root/storage/framework/views/*.php") as $f) { if(@unlink($f)) $cleared++; }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }
foreach (glob("$root/storage/framework/cache/data/*/*") as $f) { if(is_file($f)) @unlink($f); }
$results['4_cache_cleared'] = $cleared;

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
