<?php
/**
 * ProductDetail.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2022-06-23 11:33:06
 * @modified   2022-06-23 11:33:06
 */

namespace Beike\Shop\Http\Resources;

use Beike\Repositories\ProductRepo;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetail extends JsonResource
{
    /**
     * @throws \Exception
     */
    public function toArray($request): array
    {
        $attributes = [];
        foreach ($this->attributes as $ProductAttribute) {
            if (! isset($attributes[$ProductAttribute->attribute->attribute_group_id]['attribute_group_name'])) {
                $attributes[$ProductAttribute->attribute->attribute_group_id]['attribute_group_name'] = $ProductAttribute->attribute->attributeGroup->description->name;
            }
            $attributes[$ProductAttribute->attribute->attribute_group_id]['attributes'][] = [
                'attribute'       => $ProductAttribute->attribute->description->name,
                'attribute_value' => $ProductAttribute->attributeValue->description->name,
            ];
        }
        $cate_nameinfo = ProductRepo::getCateName($this->id);
        if(!empty($cate_nameinfo)){
            $cate_name = $cate_nameinfo['name'];
        }else{
            $cate_name = '';
        }
        $day = '天';
        $quality = '见包装';
        if (locale() == 'mn'){
            $day = 'хоног';
            $quality = 'Сав баглаа боодол';
        }elseif (locale() == 'en'){
            $day = 'Days';
            $quality = 'See packaging';
        }elseif (locale() == 'ru'){
            $day = 'дней';
            $quality = 'Посмотреть упаковку';
        }
        if($this->min_purchasing_price==$this->warehouse_commodity_price){
            $approximately = 0;
        }else{
            $approximately = currency_format($this->warehouse_commodity_price);
        }
        $data = [
            'id'               => $this->id,
            'name'             => $this->description->name             ?? '',
            'description'      => $this->description->content          ?? '',
            'meta_title'       => $this->description->meta_title       ?? '',
            'meta_keywords'    => $this->description->meta_keywords    ?? '',
            'meta_description' => $this->description->meta_description ?? '',
            'brand_id'         => $this->brand->id                     ?? 0,
            'brand_name'       => $this->brand->name                   ?? '',
            'goods_code'       => $this->goods_code                    ?? '',
            'video'            => $this->video                         ?? '',
            'min'            => $this->min                         ?? 0,
            'gunit_max'            => $this->description->gunit_max_des                         ?? '',
            'gnum_midd'            => $this->gnum_midd                         ?? 0,
            'gunit_midd'            => $this->description->gunit_midd_des                         ?? '',
            'gnum_min'            => $this->gnum_min                         ?? 0,
            'gunit_min'            => $this->description->gunit_min_des                         ?? '',
            'quality'            => $this->quality?$this->quality.$day:$quality,
            'remark'            => $this->remark                         ?? '',
            'earliest_date'            => $this->earliest_date ?? '',
            // 'min_purchasing_unit'            => $this->min_purchasing_unit ?? '',
            'min_purchasing_unit'            => $this->description->min_purchasing_unit_des                         ?? '',
            'min_purchasing_price'            => $this->min_purchasing_price ?? 0,
            'approximately'            => $approximately,
            'integral'            => $this->integral                         ?? 0,
            'cate_name'            => $cate_name                         ,
            'images'           => array_map(function ($image) {
                return [
                    'preview' => image_resize($image, 500, 500),
                    'popup'   => image_resize($image, 800, 800),
                    'thumb'   => image_resize($image, 150, 150),
                ];
            }, $this->images ?? []),
            'attributes'       => $attributes,
            'variables'        => $this->decodeVariables($this->variables),
            'skus'             => SkuDetail::collection($this->skus)->jsonSerialize(),
            'in_wishlist'      => $this->inCurrentWishlist->id ?? 0,
            'active'           => (bool) $this->active,
        ];

        return hook_filter('resource.product.detail', $data);
    }

    /**
     * 处理多规格商品数据
     *
     * @param $variables
     * @return array|array[]
     * @throws \Exception
     */
    private function decodeVariables($variables): array
    {
        $lang = locale();
        if (empty($variables)) {
            return [];
        }

        return array_map(function ($item) use ($lang) {
            return [
                'name'   => $item['name'][$lang] ?? '',
                'values' => array_map(function ($item) use ($lang) {
                    return [
                        'name'  => $item['name'][$lang] ?? '',
                        'image' => $item['image'] ? image_resize($item['image']) : '',
                    ];
                }, $item['values']),
            ];
        }, $variables);
    }
}
