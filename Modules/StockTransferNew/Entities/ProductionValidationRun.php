<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductionValidationRun extends Model
{
    protected $table = 'stn_production_validation_runs';

    protected $guarded = ['id'];

    protected $casts = [
        'business_id' => 'integer',
        'location_id' => 'integer',
        'store_id' => 'integer',
        'checked_by' => 'integer',
        'total_checks' => 'integer',
        'passed_checks' => 'integer',
        'warning_checks' => 'integer',
        'failed_checks' => 'integer',
        'payload' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
