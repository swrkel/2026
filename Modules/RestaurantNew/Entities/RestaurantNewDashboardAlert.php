<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewDashboardAlert extends Model
{
    protected $table = 'restaurant_new_dashboard_alerts';
    protected $guarded = ['id'];
    protected $casts = [
        'meta' => 'array',
        'is_read' => 'boolean',
    ];
}
