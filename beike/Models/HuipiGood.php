<?php

namespace Beike\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class HuipiGood  extends Base
{
    use HasFactory;

    protected $fillable = [
        'goods_id',
        'site_id',
        'goods_name',
        'goods_type',
        'sub_title',
        'goods_cover',
        'goods_image',
        'goods_category',
        'goods_mall_category',
        'goods_desc',
        'brand_id',
        'label_ids',
        'service_ids',
        'unit',
        'stock',
        'sale_num',
        'virtual_sale_num',
        'status',
        'audit_status',
        'audit_reason',
        'sort',
        'delivery_type',
        'is_free_shipping',
        'fee_type',
        'delivery_money',
        'delivery_template_id',
        'mall_attr_id',
        'shop_attr_id',
        'attr_format',
        'is_discount',
        'member_discount',
        'supplier_id',
        'create_time',
        'update_time',
        'delete_time',
        'shop_sort',
        'virtual_auto_delivery',
        'virtual_receive_type',
        'virtual_verify_type',
        'virtual_indate',
        'gunit_max',
        'gnum_midd',
        'gunit_midd',
        'gnum_min',
        'gunit_min',
        'quality',
        'origin',
        'unit_min',
        'buy_num_min',
        'cash_price_big',
        'cash_price_small',
        'tax_price',
        'goods_code',
        'is_synch',
        'is_cate_update',
        'level'
    ];


}
