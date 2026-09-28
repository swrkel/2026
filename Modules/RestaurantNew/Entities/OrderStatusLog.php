<?php
namespace Modules\RestaurantNew\Entities;

class OrderStatusLog extends RestnewModel
{
    protected $table = 'restnew_order_status_logs';
    protected $casts = [
        'metadata' => 'array',
    ];
}
