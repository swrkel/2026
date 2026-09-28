<?php

namespace Modules\DistributionNew\Entities\ProductionCompletion;

use Illuminate\Database\Eloquent\Model;

class PerformanceCache extends Model
{
    protected $table = 'disnew_performance_cache';
    protected $guarded = ['id'];
}
