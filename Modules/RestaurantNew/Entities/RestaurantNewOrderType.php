<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewOrderType extends RestaurantNewBaseModel
{
    protected $table = 'rn_order_types';

    protected $casts = [
        'requires_table' => 'boolean',
        'requires_customer' => 'boolean',
        'allow_delivery' => 'boolean',
        'is_active' => 'boolean',
    ];
}
