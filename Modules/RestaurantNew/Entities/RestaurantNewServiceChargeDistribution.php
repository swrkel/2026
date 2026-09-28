<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewServiceChargeDistribution extends RestaurantNewBaseModel
{
    protected $table = 'rn_service_charge_distributions';

    protected $casts = [
        'base_amount' => 'decimal:4',
        'share_percent' => 'decimal:4',
        'distributed_amount' => 'decimal:4',
        'distribution_date' => 'date',
    ];
}
