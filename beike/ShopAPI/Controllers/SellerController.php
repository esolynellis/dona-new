<?php
/**
 * SellerController.php
 * 该文件为多商家系统对应接口，需购买安装多商家系统才能使用
 *
 * @copyright  2024 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     TL <mengwb@guangda.work>
 * @created    2024-01-03 15:50:32
 * @modified   2024-01-03 15:50:32
 */

namespace Beike\ShopAPI\Controllers;

use App\Http\Controllers\Controller;
use Beike\Seller\Repositories\ProductRepo;
use Beike\Seller\Repositories\SellerCategoryRepo;
use Beike\Seller\Repositories\SellerRepo;
use Beike\Shop\Http\Resources\ProductSimple;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    /**
     * @throws Exception
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $products = ProductRepo::getBuilder(['active' => true, 'sort' => 'id', 'order' => 'desc'], $id)->paginate($data['per_page'] ?? 20);

        $seller = SellerRepo::find($id);
        if (! $seller) {
            throw new Exception('Error: seller id not exist!');
        }
        $data     = [
            'seller'   => $seller,
            'products' => $products,
            'items'    => ProductSimple::collection($products)->jsonSerialize(),
        ];

        return json_success(trans('common.get_success'), $data);
    }

    public function category(Request $request, int $sid, int $id): JsonResponse
    {
        $category = SellerCategoryRepo::find($id);
        if (! $category || ! $category->active) {
            throw new Exception('Error: category not exist or not active!');
        }
        $filterData = $request->only('attr', 'price', 'sort', 'order', 'per_page');
        $products   = ProductRepo::getProductsBySellerCategory($category->id, $filterData);

        $data       = [
            'products_format' => ProductSimple::collection($products)->jsonSerialize(),
            'products'        => $products,
        ];

        return json_success(trans('common.get_success'), $data);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function categories(Request $request, int $sid): JsonResponse
    {
        $categories = SellerCategoryRepo::list($sid);
        $data       = [
            'categories'        => $categories,
        ];

        return json_success(trans('common.get_success'), $data);
    }
}
