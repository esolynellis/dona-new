<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$target = __DIR__ . '/../app/Http/Middleware/SetLocaleShopApi.php';

$content = <<<'PHP'
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocaleShopApi
{
    public function handle(Request $request, Closure $next): mixed
    {
        $locale = $request->header('locale');
        if (empty($locale)) {
            $locale = $request->get('locale');
        }
        $locale = $locale ?? 'mn';

        // This shop is Mongolia-only: treat Chinese locale as Mongolian
        if ($locale === 'zh_cn' || $locale === 'zh_hk') {
            $locale = 'mn';
        }

        $languages = languages()->toArray();
        register('locale', $locale);
        if (in_array($locale, $languages)) {
            App::setLocale($locale);
        } else {
            App::setLocale('mn');
        }

        return $next($request);
    }
}
PHP;

$result = file_put_contents($target, $content);

// Clear bootstrap cache
foreach (glob(__DIR__ . '/../bootstrap/cache/*.php') as $f) {
    @unlink($f);
}

header('Content-Type: application/json');
echo json_encode([
    'success' => $result !== false,
    'bytes'   => $result,
    'writable' => is_writable($target),
], JSON_PRETTY_PRINT);
