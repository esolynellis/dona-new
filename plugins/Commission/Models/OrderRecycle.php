<?php
/**

 *
 * @author     村长+ <178277164@qq.com>
 */

namespace Plugin\Wallet\Models;

use Beike\Models\Customer;
use Beike\Models\Order;
use Beike\Models\OrderProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRecycle extends Model
{

    public $table = 'order_recycle';

    public $fillable = [
        'customer_id',
        'order_id',
        'order_product_id',
        'amount',
        'total_amount',
        'status'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }


    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function order_product(): BelongsTo
    {
        return $this->belongsTo(OrderProduct::class);
    }


}
