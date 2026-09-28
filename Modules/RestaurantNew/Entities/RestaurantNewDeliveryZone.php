<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewDeliveryZone extends RestaurantNewBaseModel
{
    protected $table = 'rn_delivery_zones';

    protected $casts = [
        'base_delivery_charge' => 'decimal:4',
        'free_delivery_minimum' => 'decimal:4',
        'estimated_minutes' => 'integer',
        'is_active' => 'boolean',
    ];
}
