<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class StaffRosterShift extends Model
{
    protected $table = 'hm_staff_roster_shifts';
    protected $guarded = ['id'];
}
