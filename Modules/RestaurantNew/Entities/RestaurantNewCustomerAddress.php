<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewCustomerAddress extends RestaurantNewBaseModel
{
    protected $table = 'rn_customer_addresses';

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
