<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyTier extends Model
{
    use SoftDeletes;

    protected $table = 'hm_loyalty_tiers';
    protected $guarded = ['id'];
}
