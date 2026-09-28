<?php

namespace Modules\RestaurantNew\Entities;

use App\BusinessLocation;

class StockMovement extends RestnewModel
{
    protected $table = 'restnew_stock_movements';

    protected $casts = [
        'metadata' => 'array',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }
}
