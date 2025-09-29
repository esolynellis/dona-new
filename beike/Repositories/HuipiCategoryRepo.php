<?php

namespace Beike\Repositories;

use Beike\Models\HuipiCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class HuipiCategoryRepo
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
     * 获取商品筛选builder
     * @param array $filters
     * @return Builder
     */
    public static function getBuilder(array $filters = []): Builder
    {
        $builder = HuipiCategory::query();
        if (isset($filters['category_name'])) {
            $builder->where('category_name', 'like', "%{$filters['category_name']}%");
        }
        $builder->orderByDesc('create_time');
//        $sql = $builder->toSql();
//        var_dump($sql);die;
        return $builder;
    }
}
