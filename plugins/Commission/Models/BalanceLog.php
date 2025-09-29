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

class BalanceLog extends Model
{

    const ACTION_APPLY_UNPAID = 'apply_unpaid';//待支付

    const ACTION_APPLY_PAID = 'apply_paid';//申请通过

    const ACTION_APPLY_REFUSE = 'apply_refuse';//拒绝申请




    public $fillable = [
        'customer_id',
        'customer_account',
        'bank_user_name',
        'bank_name',
        'bank_code',
        'amount',
        'note',
        'status'
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
