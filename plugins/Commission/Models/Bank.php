<?php
/**

 *
 * @author     村长+ <178277164@qq.com>
 */

namespace Plugin\Commission\Models;

use Beike\Models\Customer;
use Beike\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bank extends Model
{





    public $fillable = [
        'customer_id',
        'bank_user_name',
        'bank_name',
        'bank_code',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }



    private function format_money($amount)
    {
        $amount = bcdiv($amount, 100, 2);
        return number_format((double)$amount, 2, '.', ',');;
    }
}
