<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyLoyaltyTransaction extends Model
{
    protected $table = 'bs_loyalty_transactions';
    protected $guarded = ['id'];
}
