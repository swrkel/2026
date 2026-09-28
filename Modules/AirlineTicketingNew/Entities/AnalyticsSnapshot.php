<?php
namespace Modules\AirlineTicketingNew\Entities;

class AnalyticsSnapshot extends BaseAirlineTicketingModel
{
    protected $table = 'atn_analytics_snapshots';
    protected $guarded = ['id'];
    protected $casts = [
        'snapshot_date' => 'date',
        'metrics_json' => 'array',
    ];
}
