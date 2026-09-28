<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewReservation extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['reservation_date'=>'date','is_vip'=>'boolean'];
}
