<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewAiRecommendation extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['source_payload'=>'array','action_payload'=>'array','resolved_at'=>'datetime'];
}
