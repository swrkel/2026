<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;

class DisnewLoadingPlan extends Model
{
    protected $table = 'disnew_loading_plans';
    protected $guarded = ['id'];

    public function lines()
    {
        return $this->hasMany(DisnewLoadingPlanLine::class, 'loading_plan_id');
    }
}
