<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewDeliveryStatusLog extends RestaurantNewBaseModel
{
    protected $table = 'rn_delivery_status_logs';

    protected $casts = [
        'changed_at' => 'datetime',
    ];
}
