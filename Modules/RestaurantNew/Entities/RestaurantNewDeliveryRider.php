<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewDeliveryRider extends RestaurantNewBaseModel
{
    protected $table = 'rn_delivery_riders';

    protected $casts = [
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'last_assigned_at' => 'datetime',
    ];
}
