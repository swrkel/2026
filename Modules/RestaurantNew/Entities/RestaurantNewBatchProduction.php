<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewBatchProduction extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['started_at'=>'datetime','completed_at'=>'datetime','yield_payload'=>'array'];
}
