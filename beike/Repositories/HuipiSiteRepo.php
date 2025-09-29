<?php

namespace Beike\Repositories;

use Beike\Models\HuipiSite;
use Beike\Shop\Http\Resources\BrandDetail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class HuipiSiteRepo
{
    /**
     * @param $filters
     * @return LengthAwarePaginator
     */
    public static function list($filters): LengthAwarePaginator
    {
        $builder = self::getBuilder($filters);
        return $builder->paginate(perPage())->withQueryString();
    }

    /**
     * 获取商品品牌筛选builder
     * @param array $filters
     * @return Builder
     */
    public static function getBuilder(array $filters = []): Builder
    {
        $builder = HuipiSite::query();
        if (isset($filters['site_name'])) {
            $builder->where('site_name', 'like', "%{$filters['site_name']}%");
        }
        $builder->orderByDesc('create_time');
//        $sql = $builder->toSql();
//        var_dump($sql);die;
        return $builder;
    }
}
