<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HrAttendanceSession extends Model
{
    protected $table = 'hr_attendance_sessions';
    protected $guarded = ['id'];
}
