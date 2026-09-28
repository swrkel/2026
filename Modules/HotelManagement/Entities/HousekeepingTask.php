<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HousekeepingTask extends Model
{
    use SoftDeletes;
    protected $table = 'hm_housekeeping_tasks';
    protected $guarded = ['id'];
}
