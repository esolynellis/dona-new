<?php

namespace Beike\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductDescription extends Base
{
    use HasFactory;

    protected $fillable = ['locale', 'product_id', 'name', 'content', 'meta_title', 'meta_description','gunit_max_des','gunit_midd_des','gunit_min_des','min_purchasing_unit_des','meta_keywords'];
}
