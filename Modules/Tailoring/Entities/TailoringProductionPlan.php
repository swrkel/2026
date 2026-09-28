<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringProductionPlan extends Model
{
    protected $table = 'tailoring_production_plans';
    protected $guarded = ['id'];
    protected $casts = ['plan_date'=>'date'];
}
