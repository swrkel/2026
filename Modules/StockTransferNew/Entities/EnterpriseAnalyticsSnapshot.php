<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class EnterpriseAnalyticsSnapshot extends Model
{
    protected $table = 'stn_enterprise_analytics_snapshots';

    protected $fillable = [
        'business_id', 'location_id', 'store_id', 'period_from', 'period_to',
        'snapshot_type', 'metric_key', 'metric_label', 'metric_value',
        'metric_payload', 'generated_by', 'generated_at',
    ];

    protected $casts = [
        'metric_payload' => 'array',
        'generated_at' => 'datetime',
    ];
}
