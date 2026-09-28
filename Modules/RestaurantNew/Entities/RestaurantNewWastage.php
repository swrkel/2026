<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewWastage extends Model
{
    protected $table = 'restaurant_new_wastages';

    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'wastage_date' => 'date',
    ];
}
