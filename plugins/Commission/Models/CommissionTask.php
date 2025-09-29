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

class CommissionTask extends Model
{
    public $table = 'commission_task';

}
