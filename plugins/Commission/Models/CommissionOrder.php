<?php

namespace Plugin\Commission\Models;

use Beike\Models\Customer;
use Beike\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionOrder extends Model
{


    public $table = 'commission_orders';


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

}
