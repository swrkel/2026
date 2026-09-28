<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class StaffAttendanceLog extends Model
{
    protected $table = 'hm_staff_attendance_logs';
    protected $guarded = ['id'];
}
