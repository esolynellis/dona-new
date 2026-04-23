<?php

namespace Beike\Admin\Http\Controllers;

use Beike\Repositories\HuipiGoodsRepo;
use Beike\Repositories\HuipiSiteRepo;
use Illuminate\Http\Request;

class HuipiSiteController extends Controller
{
    /**
     * 显示品牌列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $sites = HuipiSiteRepo::list($request->only('site_name'));

        $data   = [
            'sites' => $sites,
        ];
        $data = hook_filter('admin.huipi_sites.index.data', $data);
        if ($request->expectsJson()) {
            return json_success(trans('common.success'), $data);
        }

        return view('admin::pages.huipi_site.index', $data);
    }
}
