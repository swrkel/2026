<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewStockMovement extends Model
{
    protected $table = 'restaurant_new_stock_movements';

    protected $guarded = ['id'];

    protected $casts = [
        'quantity_in' => 'decimal:4',
        'quantity_out' => 'decimal:4',
        'balance_qty' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];
}
