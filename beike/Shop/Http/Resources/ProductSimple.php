<?php
/**
 * ProductSimple.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2022-06-23 11:33:06
 * @modified   2022-06-23 11:33:06
 */

namespace Beike\Shop\Http\Resources;

use Beike\Models\Cart;
use Beike\Models\CartProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSimple extends JsonResource
{
    /**
     * 图片列表页Item
     *
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function toArray($request): array
    {
        $masterSku = $this->masterSku;
        if (empty($masterSku)) {
            throw new \Exception("invalid master sku for product {$this->id}");
        }

        $name   = $this->description->name ?? '';
        $images = $this->images;
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
            $approximately = $this->warehouse_commodity_price;
        }
        $custorm = current_customer();
        if($custorm){
            $cart_info = CartProduct::query()->where('customer_id',$custorm->id)->where('product_id',$this->id)->first();
            if(empty($cart_info)){
                $cart_num = 0;
                $cart_id = 0;
            }else{
                $cart_num = $cart_info['quantity'];
                $cart_id = $cart_info['id'];
            }
        }else{
            $cart_info = CartProduct::query()->where('customer_id',0)->where('product_id',$this->id)->first();
            if(empty($cart_info)){
                $cart_num = 0;
                $cart_id = 0;
            }else{
                $cart_num = $cart_info['quantity'];
                $cart_id = $cart_info['id'];
            }
        }

        $data = [
            'id'                  => $this->id,
            'sku_id'              => $masterSku->id,
            'name'                => $name,
            'name_format'         => $name,
            'url'                 => $this->url,
            'price'               => $masterSku->price,
            'origin_price'        => $masterSku->origin_price,
            'price_format'        => currency_format($masterSku->price),
            'origin_price_format' => currency_format($masterSku->origin_price),
            'category_id'         => $this->category_id           ?? null,
            'in_wishlist'         => $this->inCurrentWishlist->id ?? 0,
            'min'            => $this->min                         ?? 0,
            //            'gunit_max'            => $this->gunit_max                         ?? '',
//            'gnum_midd'            => $this->gnum_midd                         ?? 0,
//            'gunit_midd'            => $this->gunit_midd                         ?? '',
//            'gnum_min'            => $this->gnum_min                         ?? 0,
//            'gunit_min'            => $this->gunit_min                         ?? '',
            'gunit_max'            => $this->description->gunit_max_des                         ?? '',
            'gnum_midd'            => $this->gnum_midd                         ?? 0,
            'gunit_midd'            => $this->description->gunit_midd_des                         ?? '',
            'gnum_min'            => $this->gnum_min                         ?? 0,
            'gunit_min'            => $this->description->gunit_min_des                         ?? '',
            'quality'            => $this->quality?$this->quality.$day:$quality,
            'remark'            => $this->remark                         ?? '',
            'earliest_date'            => $this->earliest_date?? '',
            'approximately'            => $approximately,
            // 'min_purchasing_unit'            => $this->min_purchasing_unit ?? '',
            'min_purchasing_unit'            => $this->description->min_purchasing_unit_des                         ?? '',
            'min_purchasing_price'            => $this->min_purchasing_price ?? 0,
            'integral'         => $this->integral ?? 0,
            'cart_num'         => $cart_num,
            'cart_id'         => $cart_id,
            'images'              => array_map(function ($item) {
//                return image_resize($item, 400, 400);
                return $item.'?imageView2/2/w/400/h/400';
            }, $images),
        ];

        return hook_filter('resource.product.simple', $data);
    }
}
