<?php
namespace Modules\RestaurantNew\Entities;

class DailyClosure extends RestnewModel
{
    protected $table = 'restnew_daily_closures';
    protected $casts = [
        'closed_at' => 'datetime',
        'summary_json' => 'array',
    ];
}
