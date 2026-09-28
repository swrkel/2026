<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewOnlineChannel extends Model
{
    protected $table = 'restaurant_new_online_channels';
    protected $guarded = ['id'];
    protected $casts = ['settings' => 'array',];
}
