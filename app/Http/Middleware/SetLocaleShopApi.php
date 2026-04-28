<?php
/**
 * SetLocaleShopApi.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-08-16 16:40:19
 * @modified   2023-08-16 16:40:19
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocaleShopApi
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
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
            App::setLocale('en');
        }

        return $next($request);
    }
}
