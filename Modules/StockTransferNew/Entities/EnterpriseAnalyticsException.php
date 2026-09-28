<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class EnterpriseAnalyticsException extends Model
{
    protected $table = 'stn_enterprise_analytics_exceptions';

    protected $fillable = [
        'business_id', 'location_id', 'store_id', 'transfer_id', 'exception_type',
        'severity', 'title', 'description', 'recommended_action', 'status',
        'assigned_to', 'resolved_by', 'resolved_at', 'resolution_note',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];
}
