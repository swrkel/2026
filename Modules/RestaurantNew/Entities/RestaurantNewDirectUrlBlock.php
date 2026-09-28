<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewDirectUrlBlock extends Model
{
    protected $table = 'restaurant_new_direct_url_blocks';
    protected $guarded = ['id'];
    protected $casts = [];
}
