<?php
/**
 * HomeController.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-06-06 15:50:32
 * @modified   2023-06-06 15:50:32
 */

namespace Beike\ShopAPI\Controllers;

use App\Http\Controllers\Controller;
use Beike\Services\DesignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * @throws \Exception
     */
    public function index(): JsonResponse
    {
        $appHomeData = system_setting('base.app_home_setting');
        $modules     = $appHomeData['modules'] ?? [];

        $productCodes = ['product', 'category', 'latest'];
        $moduleItems  = [];
        $productModuleIndex = 0;

        foreach ($modules as $module) {
            $code    = $module['code'];
            $content = $module['content'];

            if ($code === 'product') {
                // Always fetch newest active products dynamically — no stale IDs
                $offset = $productModuleIndex * 8;
                $ids = DB::table('products as p')
                    ->join('product_descriptions as pd', 'p.id', '=', 'pd.product_id')
                    ->whereNull('p.deleted_at')
                    ->where('p.active', 1)
                    ->where('pd.locale', 'mn')
                    ->where('pd.name', '!=', '')
                    ->whereNotNull('p.images')
                    ->where('p.images', '!=', '[]')
                    ->orderBy('p.created_at', 'desc')
                    ->offset($offset)
                    ->limit(8)
                    ->pluck('p.id')
                    ->toArray();

                $content['products'] = $ids;
                $productModuleIndex++;
            } elseif (in_array($code, $productCodes)) {
                $content['products'] = collect($content['products'])->pluck('id')->toArray();
            }

            $moduleItems[] = [
                'code'    => $code,
                'content' => DesignService::handleModuleContent($code, $content),
            ];
        }

        return json_success(trans('common.get_success'), $moduleItems);
    }
}
