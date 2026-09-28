<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewKitchenSection extends RestaurantNewBaseModel
{
    protected $table = 'rn_kitchen_sections';

    protected $casts = [
        'print_kot' => 'boolean',
        'show_on_kds' => 'boolean',
        'is_active' => 'boolean',
    ];
}
