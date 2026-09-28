<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewCashMovement extends RestaurantNewBaseModel
{
    protected $table = 'rn_cash_movements';

    protected $casts = [
        'amount' => 'decimal:4',
    ];
}
