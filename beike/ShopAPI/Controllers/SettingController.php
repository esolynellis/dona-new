<?php
/**
 * SettingController.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-08-16 18:11:22
 * @modified   2023-08-16 18:11:22
 */

namespace Beike\ShopAPI\Controllers;

use App\Http\Controllers\Controller;
use Beike\Repositories\SettingRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request): JsonResponse|array
    {
        try {
            $data                    = SettingRepo::getMobileSetting();
            $data['is_multi_seller'] = plugin_setting('multi_seller.status', 0);

            return $data;
        } catch (\Exception $e) {
            return json_fail($e->getMessage());
        }
    }
}
