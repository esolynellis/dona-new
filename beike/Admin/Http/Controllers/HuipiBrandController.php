<?php

namespace Beike\Admin\Http\Controllers;
use Beike\Models\HuipiBrand;
use Beike\Repositories\HuipiBrandRepo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class HuipiBrandController extends Controller
{
    /**
     * 显示品牌列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $brands = HuipiBrandRepo::list($request->only('brand_name'));

        $data   = [
            'brands' => $brands,
        ];
        $data = hook_filter('admin.huipi_brands.index.data', $data);
        if ($request->expectsJson()) {
            return json_success(trans('common.success'), $data);
        }

        return view('admin::pages.huipi_brand.index', $data);
    }
}
