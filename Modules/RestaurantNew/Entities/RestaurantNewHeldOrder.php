<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewHeldOrder extends Model
{
    protected $table = 'rn_held_orders';
    protected $guarded = ['id'];

    protected $casts = [
        'snapshot' => 'array',
    ];
}
