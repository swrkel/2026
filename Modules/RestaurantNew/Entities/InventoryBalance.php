<?php

namespace Modules\RestaurantNew\Entities;

use App\BusinessLocation;

class InventoryBalance extends RestnewModel
{
    protected $table = 'restnew_inventory_balances';

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }
}
