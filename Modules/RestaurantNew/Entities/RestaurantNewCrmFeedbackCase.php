<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewCrmFeedbackCase extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['resolved_at'=>'datetime'];
}
