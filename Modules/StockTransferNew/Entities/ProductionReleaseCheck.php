<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductionReleaseCheck extends Model
{
    protected $table = 'stn_production_release_checks';

    protected $fillable = [
        'business_id', 'location_id', 'store_id', 'check_code', 'check_name',
        'check_group', 'status', 'severity', 'message', 'checked_by', 'checked_at'
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];
}
