<?php

namespace Beike\Admin\Http\Controllers;

use Beike\Repositories\HuipiCategoryRepo;
use Illuminate\Http\Request;

class HuipiCategoryController extends Controller
{
    /**
     * 显示品牌列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $categorys = HuipiCategoryRepo::list($request->only('category_name'));

        $data   = [
            'categorys' => $categorys,
        ];
        $data = hook_filter('admin.huipi_categorys.index.data', $data);
        if ($request->expectsJson()) {
            return json_success(trans('common.success'), $data);
        }

        return view('admin::pages.huipi_category.index', $data);
    }
}
