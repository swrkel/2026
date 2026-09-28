<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class StaffRole extends Model
{
    protected $table = 'hm_staff_roles';
    protected $guarded = ['id'];
}
