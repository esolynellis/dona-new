<?php

namespace Beike\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductDescription extends Base
{
    use HasFactory;

    protected $fillable = ['locale', 'product_id', 'name', 'content', 'meta_title', 'meta_description', 'meta_keywords'];
}
