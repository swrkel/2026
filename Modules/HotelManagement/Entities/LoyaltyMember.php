<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyMember extends Model
{
    use SoftDeletes;

    protected $table = 'hm_loyalty_members';
    protected $guarded = ['id'];
}
