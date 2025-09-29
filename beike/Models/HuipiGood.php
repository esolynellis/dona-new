<?php

namespace Beike\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class HuipiGood  extends Base
{
    use HasFactory;

    protected $fillable = ['goods_id','goods_name','goods_type','sub_title','goods_cover','goods_image','goods_category','goods_mall_category','goods_desc','brand_id','unit','stock','sale_num','status','gunit_max','gnum_midd','gunit_midd','gnum_min','gunit_min','quality','origin','unit_min','buy_num_min','cash_price_big','cash_price_small','goods_code'];
}
