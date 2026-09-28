<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewLoyaltyLink extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['linked_at'=>'datetime','integration_meta'=>'array','is_active'=>'boolean'];
}
