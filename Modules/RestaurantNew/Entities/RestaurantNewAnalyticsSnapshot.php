<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewAnalyticsSnapshot extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['snapshot_date'=>'date','kpi_payload'=>'array'];
}
