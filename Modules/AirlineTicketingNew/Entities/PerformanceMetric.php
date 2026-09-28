<?php
namespace Modules\AirlineTicketingNew\Entities;

class PerformanceMetric extends BaseAirlineTicketingModel
{
    protected $table = 'atn_performance_metrics';
    protected $guarded = ['id'];
    protected $casts = [
        'metric_value' => 'decimal:4',
        'recorded_at' => 'datetime',
        'context_json' => 'array',
    ];
}
