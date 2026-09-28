<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MinibarConsumption extends Model
{
    use SoftDeletes;

    protected $table = 'hm_minibar_consumptions';
    protected $guarded = [];
}
