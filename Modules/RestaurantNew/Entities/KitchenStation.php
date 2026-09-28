<?php
namespace Modules\RestaurantNew\Entities;

class KitchenStation extends RestnewModel
{
    protected $table = 'restnew_kitchen_stations';
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
