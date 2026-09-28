<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewDeliveryOrder extends RestaurantNewBaseModel
{
    protected $table = 'rn_delivery_orders';

    protected $casts = [
        'delivery_charge' => 'decimal:4',
        'cod_amount' => 'decimal:4',
        'card_amount' => 'decimal:4',
        'assigned_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];
}
