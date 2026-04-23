<?php

namespace Beike\Admin\Http\Controllers;

use Beike\Models\HuipiGood;
use Beike\Models\Product;           // mn.products
use Beike\Models\ProductSku;
use Beike\Models\ProductCategory;
use Beike\Models\ProductDescription;
use Beike\Repositories\HuipiGoodsRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Beike\Admin\Services\GoodsSyncService;

class HuipiGoodsController extends Controller
{
    private $syncService;

    public function __construct(GoodsSyncService $syncService)
    {
        $this->syncService = $syncService;
    }
    /**
     * 显示品牌列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $goods = HuipiGoodsRepo::list($request->only('goods_name'));

        $data   = [
            'goods' => $goods,
        ];
        $data = hook_filter('admin.huipi_goods.index.data', $data);
        if ($request->expectsJson()) {
            return json_success(trans('common.success'), $data);
        }

        return view('admin::pages.huipi_goods.index', $data);
    }

    /**
     * 批量同步（Laravel 事务版）
     */
//    public function batchSync(Request $request)
//    {
//        $ids = $request->input('ids', []);
//        if (!is_array($ids) || !$ids) {
//            return response()->json(['code' => 0, 'msg' => '请选择商品']);
//        }
//        $goods = HuipiGoodsRepo::batchSync($request->only('ids'));
//    }

    /**
     * 同步商品接口
     */
    public function batchSync(Request $request)
    {
        $goodsIds = $request->input('ids');
        if (!is_array($goodsIds) || !$goodsIds) {
            return response()->json(['code' => 0, 'msg' => '请选择商品']);
        }

        // 去重并限制最大数量
        $goodsIds = array_unique($goodsIds);
        $goodsIds = array_slice($goodsIds, 0, 1000);

        $result = $this->syncService->syncGoodsByIds($goodsIds);

        return response()->json($result);
    }
}
