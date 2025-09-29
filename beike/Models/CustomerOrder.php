<?php

namespace Beike\Models;

use Beike\Notifications\NewOrderNotification;
use Beike\Notifications\UpdateOrderNotification;
use Beike\Services\StateMachineService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class CustomerOrder extends Base
{
    use Notifiable;
    use SoftDeletes;
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

}
