<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringDeliverySchedule extends Model
{
    protected $table = 'tailoring_delivery_schedules';
    protected $guarded = ['id'];
    protected $casts = ['delivery_date'=>'date','delivered_at'=>'datetime'];
}
