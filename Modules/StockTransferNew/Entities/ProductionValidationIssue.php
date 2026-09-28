<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductionValidationIssue extends Model
{
    protected $table = 'stn_production_validation_issues';

    protected $guarded = ['id'];

    protected $casts = [
        'run_id' => 'integer',
        'business_id' => 'integer',
        'location_id' => 'integer',
        'store_id' => 'integer',
        'severity_score' => 'integer',
        'is_resolved' => 'boolean',
        'resolved_by' => 'integer',
        'resolved_at' => 'datetime',
    ];
}
