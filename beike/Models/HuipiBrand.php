<?php

namespace Beike\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class HuipiBrand extends Base
{
    use HasFactory;

    protected $fillable = ['brand_id', 'site_id', 'brand_name','logo','desc', 'sort', 'create_time','update_time','delete_time'];

    public function huipi_site()
    {
        return $this->belongsTo(HuipiSite::class, 'site_id', 'site_id');
    }
}
