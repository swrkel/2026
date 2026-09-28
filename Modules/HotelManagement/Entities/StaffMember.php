<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    protected $table = 'hm_staff_members';
    protected $guarded = ['id'];
}
