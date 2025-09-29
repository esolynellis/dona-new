<?php

namespace Beike\Admin\Services;
use Beike\Models\HuipiGood as HuipiGoods;
use Beike\Models\Product;
use Beike\Models\ProductSku;
use Beike\Models\ProductCategory;
use Beike\Models\ProductDescription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoodsSyncService
{
    /**
     * 根据商品ID同步商品数据
     */
    public function syncGoodsByIds(array $goodsIds)
    {
        try {
            // 获取有效的商品数据
            $goodsList = HuipiGoods::where('delete_time', '0')
                ->where('status', '1')
                ->whereIn('goods_id', $goodsIds)
                ->get();

            if ($goodsList->isEmpty()) {
                return [
                    'code' => 0,
                    'msg' => '未找到有效的商品数据。',
                    'data' => []
                ];
            }
            $updateCount = 0;
            $insertCount = 0;
            $results = [];

            foreach ($goodsList as $goods) {
                $syncResult = $this->syncSingleGoods($goods);

                if ($syncResult['success']) {
                    if ($syncResult['type'] === 'insert') {
                        $insertCount++;
                    } else {
                        $updateCount++;
                    }
                }

                $results[] = $syncResult;
            }

            $totalSynced = $updateCount + $insertCount;

            return [
                'code' => 1,
                'msg' => "成功同步 {$totalSynced} 条商品数据，其中更新 {$updateCount} 条，插入 {$insertCount} 条",
                'data' => [
                    'total' => $totalSynced,
                    'inserted' => $insertCount,
                    'updated' => $updateCount,
                    'details' => $results
                ]
            ];

        } catch (\Exception $e) {
            Log::error('商品同步失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'code' => 0,
                'msg' => '同步失败: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * 同步单个商品
     */
    private function syncSingleGoods(HuipiGoods $goods)
    {
        // 检查商品是否已存在
        $existingProduct = Product::where('goods_id', $goods->goods_id)->first();

        if ($existingProduct) {
            return $this->updateExistingGoods($goods, $existingProduct);
        } else {
            return $this->insertNewGoods($goods);
        }
    }

    /**
     * 更新已存在的商品
     */
    private function updateExistingGoods(HuipiGoods $goods, Product $existingProduct)
    {
        try {
            DB::beginTransaction();
            // 构建更新数据
            $updateData = $this->buildProductData($goods);
            $updateData['updated_at'] = now();

            // 移除创建时间字段
            unset($updateData['created_at']);
            // 更新商品主表
            $existingProduct->update($updateData);

            // 检查是否需要更新描述信息
            $descriptionUpdate = [];
            $needUpdateDescription = false;

            $fieldsToCheck = [
                'gunit_max' => 'gunit_max_des',
                'gunit_midd' => 'gunit_midd_des',
                'gunit_min' => 'gunit_min_des',
                'goods_name' => 'name'
            ];

            foreach ($fieldsToCheck as $goodsField => $descField) {
                if ($goods->$goodsField != $existingProduct->$goodsField) {
                    $descriptionUpdate[$descField] = $goods->$goodsField;
//                    $descriptionUpdate[str_replace('_des', '_need', $descField)] = 1;
                    $needUpdateDescription = true;
                }
            }

            // 更新商品描述
            if ($needUpdateDescription) {
                ProductDescription::where('product_id', $existingProduct->id)
                    ->where('locale', 'zh_cn')
                    ->update($descriptionUpdate);
            }

            // 检查价格是否需要更新
            if ($goods->cash_price_small != $existingProduct->cash_price_small) {
                ProductSku::where('product_id', $existingProduct->id)
                    ->update([
                        'price' => round($goods->cash_price_small, 2),
                        'origin_price' => round($goods->cash_price_small, 2),
                        'cost_price' => round($goods->cash_price_small, 2),
                    ]);
            }

            // 检查分类是否需要更新
            if ($goods->goods_mall_category != $existingProduct->goods_mall_category) {
                ProductCategory::where('product_id', $existingProduct->id)
                    ->update([
                        'category_id' => $goods->goods_mall_category,
                    ]);
            }

            DB::commit();

            return [
                'success' => true,
                'type' => 'update',
                'goods_id' => $goods->goods_id,
                'product_id' => $existingProduct->id,
                'message' => '商品更新成功'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 插入新商品
     */
    private function insertNewGoods(HuipiGoods $goods)
    {
        try {
            DB::beginTransaction();
            // 构建商品数据
            $productData = $this->buildProductData($goods);
            $productData['created_at'] = now();
            $productData['updated_at'] = now();
            // 创建商品
            $product = Product::create($productData);
            // 创建SKU
            ProductSku::create([
                'product_id' => $product->id,
                'variants' => '',
                'model' => 'default',
                'sku' => $goods->goods_id,
                'price' => round($goods->cash_price_small, 2),
                'origin_price' => round($goods->cash_price_small, 2),
                'cost_price' => round($goods->cash_price_small, 2),
                'quantity' => 999999,
                'is_default' => true,
            ]);

            // 创建分类关系
            ProductCategory::create([
                'product_id' => $product->id,
                'category_id' => $goods->goods_mall_category,
            ]);

            // 创建商品描述
            ProductDescription::create([
                'product_id' => $product->id,
                'locale' => 'zh_cn',
                'name' => $goods->goods_name,
                'gunit_max_des' => $goods->gunit_max,
                'gunit_midd_des' => $goods->gunit_midd,
                'gunit_min_des' => $goods->gunit_min,
                'gunit_max_need' => 1,
                'gunit_midd_need' => 1,
                'gunit_min_need' => 1,
                'name_need' => 1,
                'content' => '',
            ]);
            DB::commit();

            return [
                'success' => true,
                'type' => 'insert',
                'goods_id' => $goods->goods_id,
                'product_id' => $product->id,
                'message' => '商品插入成功'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 获取商品同步状态
     */
    public function getSyncStatus(array $goodsIds)
    {
        // 查询源商品是否存在
        $sourceGoods = HuipiGoods::active()
            ->whereIn('goods_id', $goodsIds)
            ->pluck('goods_id')
            ->toArray();

        // 查询目标商品是否存在
        $targetGoods = Product::whereIn('goods_id', $goodsIds)
            ->pluck('goods_id')
            ->toArray();

        $status = [];
        foreach ($goodsIds as $goodsId) {
            $status[] = [
                'goods_id' => $goodsId,
                'source_exists' => in_array($goodsId, $sourceGoods),
                'target_exists' => in_array($goodsId, $targetGoods),
            ];
        }

        return [
            'code' => 1,
            'msg' => '查询成功',
            'data' => $status
        ];
    }
    /**
     * 清理和转换数值字段
     */
    private function cleanNumericValue($value)
    {
        if ($value === null || $value === '' || $value === 'NULL') {
            return 0;
        }

        // 移除可能的空格和特殊字符
        $value = trim($value);
        $value = preg_replace('/[^\d.-]/', '', $value);

        return is_numeric($value) ? (float)$value : 0;
    }

    /**
     * 清理和转换整数字段
     */
    private function cleanIntegerValue($value)
    {
        return (int)$this->cleanNumericValue($value);
    }

    /**
     * 构建商品数据
     */
    private function buildProductData(HuipiGoods $goods)
    {
        return [
            'brand_id' => $this->cleanIntegerValue($goods->brand_id),
            'goods_id' => $this->cleanIntegerValue($goods->goods_id),
            'images' => [$goods->goods_image],
            'price' => 0,
            'video' => '',
            'active' => 1,
            'variables' => [],
            'goods_code' => $goods->goods_code ?? '',
            'gunit_max' => $goods->gunit_max ?? '',
            'gnum_midd' => $this->cleanIntegerValue($goods->gnum_midd),
            'gunit_midd' => $goods->gunit_midd ?? '',
            'gnum_min' => $this->cleanIntegerValue($goods->gnum_min),
            'gunit_min' => $goods->gunit_min ?? '',
            'min' => $this->cleanIntegerValue($goods->buy_num_min),
            'quality' => $this->cleanIntegerValue($goods->quality),
            'cash_price_small' => $this->cleanNumericValue($goods->cash_price_small),
            'goods_mall_category' => $this->cleanIntegerValue($goods->goods_mall_category),
            'goods_name' => $goods->goods_name ?? '',
            'min_purchasing_unit' => $goods->gunit_min ?? '个', // 使用最小单位作为采购单位
            'min_purchasing_price' => $this->cleanNumericValue($goods->cash_price_small),
        ];
    }
}
