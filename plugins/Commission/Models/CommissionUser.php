<?php
/**
 *
 * @author     村长+ <178277164@qq.com>
 */

namespace Plugin\Commission\Models;

use Beike\Models\Customer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionUser extends Model
{
    public $table = 'commission_users';

    public $fillable = [
        'customer_id',
        'code',
        'subordinate_count',
        'balance',
        'balance_progress',
        'total_amount',
        'status',
        'rate_1',
        'rate_2',
        'rate_3',
    ];

    protected $appends = [
        'date_at',
        'balance_format',
        'balance_progress_format',
        'total_amount_format'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);

    }

    public function getDateAtAttribute()
    {
        return Carbon::parse($this->updated_at)->format('Y-m-d H:i');
    }

    public function getBalanceFormatAttribute()
    {
        if (is_numeric($this->balance)) {
            $balance = bcdiv($this->balance, 100, 2);
            return currency_format($balance, current_currency_code());
        } else {
            return $this->balance;
        }
    }

    public function getBalanceProgressFormatAttribute()
    {
        if (is_numeric($this->balance_progress)) {
            $balance = bcdiv($this->balance_progress, 100, 2);
            return currency_format($balance, current_currency_code());
        } else {
            return $this->balance_progress;
        }
    }

    public function getTotalAmountFormatAttribute()
    {
        if (is_numeric($this->total_amount)) {
            $balance = bcdiv($this->total_amount, 100, 2);
            return currency_format($balance, current_currency_code());
        } else {
            return $this->total_amount;
        }
    }
}
