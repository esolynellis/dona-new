<?php

namespace Beike\Repositories;

use Beike\Models\HuipiGood;
use Beike\Models\Product;
use Beike\Models\ProductCategory;
use Beike\Models\ProductDescription;
use Beike\Models\ProductSku;
use Beike\Shop\Http\Resources\BrandDetail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class HuipiGoodsRepo
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
        $builder = HuipiGood::query();
        if (isset($filters['goods_name'])) {
            $builder->where('goods_name', 'like', "%{$filters['goods_name']}%");
        }
        $builder->orderByDesc('create_time');
//        $sql = $builder->toSql();
//        var_dump($sql);die;
        return $builder;
    }
    public static function getSyncBuilder(array $filters = []): Builder
    {
        $builder = HuipiGood::query();
        if (isset($filters['ids'])) {
            $builder->whereIn('goods_id',$filters['ids']);
        }
        $builder->orderByDesc('create_time');
        return $builder;
    }
    public static function batchSync($filters){
        $builder = self::getSyncBuilder($filters);
        $lists = $builder->get()->toArray();
        var_dump($lists);die;
        if (empty($lists)) {
            return response()->json(['code' => 0, 'msg' => '没有符合条件的商品']);
        }

        $updateCnt = 0;
        $insertCnt = 0;

        foreach ($lists as $hg) {
            /* ---------- 1. 主表 ---------- */
            $product = Product::where([
                'goods_code' => $hg->goods_code,
            ]);

            $exists = $product->exists;
            $product->fill([
                'brand_id'   => $hg->brand_id,
                'images'     => [$hg->goods_image],
                'price'      => 0,
                'video'      => '',
                'active'     => 1,
                'variables'  => [],
                'goods_id'   => $hg->goods_id,
                'gunit_max'  => $hg->gunit_max,
                'gnum_midd'  => $hg->gnum_midd,
                'gunit_midd' => $hg->gunit_midd,
                'gnum_min'   => $hg->gnum_min,
                'gunit_min'  => $hg->gunit_min,
                'min'        => $hg->min ?? 1,
                'quality'    => $hg->quality,
            ]);
            $product->save();
            $exists ? $updateCnt++ : $insertCnt++;

            /* ---------- 2. SKU ---------- */
            ProductSku::where('product_id', $product->id)
                ->where('is_default', 1)
                ->delete();

            ProductSku::create([
                'product_id'   => $product->id,
                'variants'     => (object)[],
                'model'        => 'default',
                'sku'          => $hg->goods_id,
                'price'        => $hg->cash_price_small,
                'origin_price' => $hg->cash_price_small,
                'cost_price'   => $hg->cash_price_small,
                'quantity'     => 999999,
                'is_default'   => 1,
            ]);

            /* ---------- 3. 分类 ---------- */
            $cateId = Category::where('huipi_pid', $hg->goods_mall_category)->value('id');
            if ($cateId) {
                ProductCategory::where('product_id', $product->id)->delete();
                ProductCategory::create([
                    'product_id'  => $product->id,
                    'category_id' => $cateId,
                ]);
            }

            /* ---------- 4. 描述 ---------- */
            ProductDescription::where('product_id', $product->id)->delete();
            ProductDescription::create([
                'product_id' => $product->id,
                'locale'     => 'zh_cn',
                'name'       => $hg->goods_name,
                'content'    => '<p><img class="img-fluid" src="' . $hg->goods_image . '" /></p>',
            ]);

            /* ---------- 5. 回写同步标志 ---------- */
            $hg->update(['is_synch' => 1]);
        }

        return response()->json([
            'code' => 1,
            'msg'  => "同步完成，更新 {$updateCnt} 条，新增 {$insertCnt} 条"
        ]);
        return $lists;
    }
}
