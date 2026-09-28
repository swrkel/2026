<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ReplenishmentConversionLog extends Model
{
    protected $table = 'stn_replenishment_conversion_logs';

    protected $fillable = [
        'business_id','recommendation_version_id','transfer_id','action','status_before','status_after',
        'qty_before','qty_after','performed_by','remarks','ip_address','user_agent'
    ];

    protected $casts = [
        'qty_before' => 'decimal:4',
        'qty_after' => 'decimal:4',
    ];
}
