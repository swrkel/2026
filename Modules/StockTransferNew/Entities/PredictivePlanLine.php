<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class PredictivePlanLine extends Model
{
    protected $table = 'stn_predictive_plan_lines';

    protected $guarded = ['id'];

    protected $casts = [
        'approved_at' => 'datetime',
    ];
}
