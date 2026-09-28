<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HousekeepingSchedule extends Model
{
    use SoftDeletes;

    protected $table = 'hm_housekeeping_schedules';
    protected $guarded = ['id'];
}
