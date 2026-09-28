<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewKpiDailySummary extends Model
{
    protected $table = 'restaurant_new_kpi_daily_summaries';
    protected $guarded = ['id'];
    protected $casts = [
        'meta' => 'array',
        'is_read' => 'boolean',
    ];
}
