<?php
namespace Modules\RestaurantNew\Entities;

class Printer extends RestnewModel
{
    protected $table = 'restnew_printers';
    protected $casts = [
        'is_active' => 'boolean',
        'settings_json' => 'array',
    ];
}
