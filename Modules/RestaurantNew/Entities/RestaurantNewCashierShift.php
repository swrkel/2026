<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewCashierShift extends RestaurantNewBaseModel
{
    protected $table = 'rn_cashier_shifts';

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:4',
        'cash_sales' => 'decimal:4',
        'card_sales' => 'decimal:4',
        'other_sales' => 'decimal:4',
        'cash_in' => 'decimal:4',
        'cash_out' => 'decimal:4',
        'expected_cash' => 'decimal:4',
        'counted_cash' => 'decimal:4',
        'shortage_excess' => 'decimal:4',
    ];
}
