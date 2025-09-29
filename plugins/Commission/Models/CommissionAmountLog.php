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

class CommissionAmountLog extends Model
{
    const ACTION_FISSION = 'fission';

    const ACTION_INIT = 'init';

    const ACTION_REG_VIP = 'reg_vip';

    const ACTION_ORDER_VIP = 'order_vip';

    const ACTION_ORDER = 'order';

    const ACTION_REFUND = 'refund';

    const ACTION_CLOSE = 'close';

    const ACTION_SYS_ADD = 'system_add';

    const ACTION_SYS_SUB = 'system_sub';

    const ACTION_PAY = 'pay';

    const ACTION_APPLY_CLOSE = 'apply_close';

    const ACTION_REFUSE_CLOSE = 'refuse_close';

    const ACTION_SCHEDULE_CLOSE = 'schedule_close';

    public $table = 'commission_amount_logs';



    protected $appends = [
        'date_at',
        'c_amount',
        'c_base_amount',
        'c_amount_format',
        'c_apply_data',
        'rate2',
    ];

    public $fillable = [
        'commission_user_id',
        'customer_id',
        'order_id',
        'action',
        'rate',
        'amount',
        'base_amount',
        'customer_group_id',
        'audit_note',
        'apply_data',
        'status'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function commissionUser(): BelongsTo
    {
        return $this->belongsTo(CommissionUser::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getDateAtAttribute()
    {
        return Carbon::parse($this->created_at)->format('Y-m-d H:i');
    }

    public function getCApplyDataAttribute()
    {
        if (empty($this->apply_data)) {
            return $this->apply_data;
        }
        return json_decode($this->apply_data, true);
    }

    public function getCAmountAttribute()
    {
        $amount = bcdiv($this->amount, 100, 2);
        return currency_format($amount);

    }

    public function getCAmountFormatAttribute()
    {
        $amount = bcdiv($this->amount, 100, 2);
        return currency_format($amount);
    }

    public function getCBaseAmountAttribute()
    {
        $amount = bcdiv($this->base_amount, 100, 2);
        return currency_format($amount);
    }

    public function getRate2Attribute()
    {
        return $this->rate . '%';
    }


    private function format_money($amount)
    {
        $amount = bcdiv($amount, 100, 2);
        return number_format((double)$amount, 2, '.', ',');;
    }


}
