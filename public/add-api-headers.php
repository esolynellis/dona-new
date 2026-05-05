<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$results = [];

// ── 1. Create ApiCacheHeaders middleware ──
$middlewarePath = "$root/app/Http/Middleware/ApiCacheHeaders.php";
$middlewareCode = '<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiCacheHeaders
{
    // Endpoints cacheable in browser for a short time
    private array $cacheable = [
        \'/api/settings\',
        \'/api/home\',
        \'/api/categories\',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if ($request->isMethod(\'GET\') && $response->getStatusCode() === 200) {
            $path = $request->getPathInfo();
            $isCacheable = false;
            foreach ($this->cacheable as $prefix) {
                if (str_starts_with($path, $prefix)) { $isCacheable = true; break; }
            }
            $response->header(
                \'Cache-Control\',
                $isCacheable ? \'public, max-age=60, s-maxage=60\' : \'no-store\'
            );
        }

        return $response;
    }
}
';

@unlink($middlewarePath);
$wrote = file_put_contents($middlewarePath, $middlewareCode);
$results['1_middleware_file'] = $wrote !== false ? 'created' : 'failed';

// ── 2. Patch Kernel.php using regex ──
$kernelPath = "$root/app/Http/Kernel.php";
$kernel = @file_get_contents($kernelPath);

if (!$kernel) {
    $results['2_kernel'] = 'cannot_read';
} elseif (strpos($kernel, 'ApiCacheHeaders') !== false) {
    $results['2_kernel'] = 'already_registered';
} else {
    // Use regex to find 'api' group and insert after the opening
    // Pattern: find 'api' key followed by [ then first entry ('throttle:...')
    $patched = preg_replace(
        "/((['\"])api\\2\s*=>\s*\[(?:\s*\/\/[^\n]*)?\s*)((['\"])throttle:[^'\"]*\\4,)/",
        '$1$3' . "\n            " . '\\App\\Http\\Middleware\\ApiCacheHeaders::class,',
        $kernel
    );

    if ($patched && $patched !== $kernel) {
        @unlink($kernelPath);
        $wrote = file_put_contents($kernelPath, $patched);
        $results['2_kernel'] = $wrote !== false ? 'OK' : 'write_failed';
    } else {
        // Fallback: just append to api group differently
        // Find exact position by searching for 'api' => [ pattern
        $apiPos = strpos($kernel, "'api'");
        if ($apiPos === false) $apiPos = strpos($kernel, '"api"');

        if ($apiPos !== false) {
            $results['2_kernel'] = 'regex_failed';
            $results['2_kernel_debug'] = substr($kernel, $apiPos, 200);
        } else {
            $results['2_kernel'] = 'api_group_not_found';
        }
    }
}

// ── 3. Clear caches ──
$cleared = 0;
foreach (glob("$root/storage/framework/views/*.php") as $f) { if(@unlink($f)) $cleared++; }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }
$results['3_caches_cleared'] = "$cleared view files";

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
