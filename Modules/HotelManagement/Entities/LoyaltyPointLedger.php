<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyPointLedger extends Model
{
    use SoftDeletes;

    protected $table = 'hm_loyalty_point_ledger';
    protected $guarded = ['id'];
}
