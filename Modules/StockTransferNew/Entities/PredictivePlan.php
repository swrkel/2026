<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class PredictivePlan extends Model
{
    protected $table = 'stn_predictive_plans';

    protected $guarded = ['id'];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(PredictivePlanLine::class, 'plan_id');
    }
}
