<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyLoyaltyTier extends Model
{
    protected $table = 'bs_loyalty_tiers';
    protected $guarded = ['id'];
}
