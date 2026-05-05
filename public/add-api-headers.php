<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$results = [];

// ── 1. Create CacheHeaders middleware ──
$middlewareDir = "$root/beike/ShopAPI";
$middlewarePath = "$root/app/Http/Middleware/ApiCacheHeaders.php";

// Create middleware file
$middlewareCode = '<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiCacheHeaders
{
    // Read-only public endpoints that can be cached by browser for 60 seconds
    private $cacheable = [
        \'/api/categories\',
        \'/api/settings\',
        \'/api/home\',
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->isMethod(\'GET\')) {
            $path = $request->getPathInfo();
            $isCacheable = false;
            foreach ($this->cacheable as $prefix) {
                if (str_starts_with($path, $prefix)) { $isCacheable = true; break; }
            }
            if ($isCacheable) {
                $response->header(\'Cache-Control\', \'public, max-age=60, s-maxage=60\');
            } else {
                $response->header(\'Cache-Control\', \'no-store\');
            }
        }

        // Performance headers
        $response->header(\'X-Powered-By\', \'DONA\');
        $response->header(\'Timing-Allow-Origin\', \'*\');

        return $response;
    }
}
';

@unlink($middlewarePath);
$wrote = file_put_contents($middlewarePath, $middlewareCode);
$results['1_middleware_file'] = $wrote !== false ? 'created' : 'failed';

// ── 2. Register middleware in Http/Kernel.php ──
$kernelPath = "$root/app/Http/Kernel.php";
$kernel = @file_get_contents($kernelPath);
if ($kernel && strpos($kernel, 'ApiCacheHeaders') === false) {
    // Add to api middleware group — match exact pattern from Kernel.php
    $old = "'api'       => [\n            'throttle:api',";
    $new = "'api'       => [\n            'throttle:api',\n            \App\Http\Middleware\ApiCacheHeaders::class,";
    if (strpos($kernel, $old) !== false) {
        $kernel = str_replace($old, $new, $kernel);
        @unlink($kernelPath);
        $wrote = file_put_contents($kernelPath, $kernel);
        $results['2_kernel_registered'] = $wrote !== false ? 'OK' : 'write_failed';
    } else {
        $results['2_kernel_registered'] = 'pattern_not_found';
        $results['2_kernel_snippet'] = substr($kernel, strpos($kernel, "'api'"), 200);
    }
} else {
    $results['2_kernel_registered'] = $kernel ? 'already_registered' : 'kernel_not_found';
}

// ── 3. Clear caches ──
$cleared = 0;
foreach (glob("$root/storage/framework/views/*.php") as $f) { if(@unlink($f)) $cleared++; }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }
$results['3_caches_cleared'] = "$cleared view files";

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
