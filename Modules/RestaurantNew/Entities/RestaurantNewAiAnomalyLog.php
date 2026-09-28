<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewAiAnomalyLog extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['metric_payload'=>'array'];
}
