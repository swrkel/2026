<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewAiInventorySignal extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['expected_shortage_date'=>'date','calculation_payload'=>'array'];
}
