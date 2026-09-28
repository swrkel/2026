<?php
namespace Modules\RestaurantNew\Entities;

class Payment extends RestnewModel
{
    protected $table = 'restnew_payments';
    protected $casts = [
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];
}
