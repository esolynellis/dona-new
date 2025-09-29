<?php

namespace Plugin\Commission\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionWithdrawalGroupItem extends Model
{


    public $table = 'commission_withdrawal_group_item';

    public function descriptions()
    {
        return $this->hasMany(CommissionWithdrawalGroupItemDescriptions::class);
    }

    public function description()
    {
        return $this->hasOne(CommissionWithdrawalGroupItemDescriptions::class)->where('locale', locale());
    }

}
