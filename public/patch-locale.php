<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$target = '/www/wwwroot/dona-new/app/Http/Middleware/SetLocaleShopApi.php';

$content = '<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocaleShopApi
{
    public function handle(Request $request, Closure $next): mixed
    {
        $locale = $request->header(\'locale\');
        if (empty($locale)) {
            $locale = $request->get(\'locale\');
        }
        $locale = $locale ?? \'mn\';

        // Mongolia-only shop: treat Chinese locale as Mongolian
        if ($locale === \'zh_cn\' || $locale === \'zh_hk\') {
            $locale = \'mn\';
        }

        $languages = languages()->toArray();
        register(\'locale\', $locale);
        if (in_array($locale, $languages)) {
            App::setLocale($locale);
        } else {
            App::setLocale(\'mn\');
        }

        return $next($request);
    }
}
';

// Directory is writable — unlink old file, write new one
$unlinkOk = @unlink($target);
$writeOk  = file_put_contents($target, $content);

// Clear caches
foreach (glob('/www/wwwroot/dona-new/bootstrap/cache/*.php') as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode([
    'unlink' => $unlinkOk,
    'write'  => $writeOk !== false,
    'bytes'  => $writeOk,
], JSON_PRETTY_PRINT);
