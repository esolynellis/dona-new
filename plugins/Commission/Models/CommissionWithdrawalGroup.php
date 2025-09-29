<?php

namespace Plugin\Commission\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionWithdrawalGroup extends Model
{


    public $table = 'commission_withdrawal_group';


    public function items()
    {
        return $this->hasMany(CommissionWithdrawalGroupItem::class, 'group_id', 'id')->orderBy('show_sort')->orderBy('id')->with([
            'description',
            'descriptions'
        ]);
    }
}
