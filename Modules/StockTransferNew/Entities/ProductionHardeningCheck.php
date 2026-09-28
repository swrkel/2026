<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductionHardeningCheck extends Model
{
    protected $table = 'stn_production_hardening_checks';

    protected $fillable = [
        'business_id',
        'location_id',
        'store_id',
        'check_code',
        'check_area',
        'check_title',
        'severity',
        'status',
        'expected_result',
        'actual_result',
        'recommendation',
        'checked_by',
        'checked_at',
    ];
}
