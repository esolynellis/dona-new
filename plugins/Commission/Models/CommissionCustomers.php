<?php
/**

 *
 * @author     村长+ <178277164@qq.com>
 */

namespace Plugin\Commission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Beike\Models\Customer;

class CommissionCustomers extends Model
{
    public $table = 'commission_customers';

    public $fillable = [
        'commission_user_id', 'consumer_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function commissionUser(): BelongsTo
    {
        return $this->belongsTo(CommissionUser::class);
    }
}
