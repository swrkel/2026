<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductionHardeningRun extends Model
{
    protected $table = 'stn_production_hardening_runs';

    protected $fillable = [
        'business_id',
        'run_no',
        'run_type',
        'status',
        'total_checks',
        'passed_checks',
        'warning_checks',
        'failed_checks',
        'started_by',
        'started_at',
        'completed_at',
        'notes',
    ];
}
