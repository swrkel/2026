<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HrAttendanceException extends Model
{
    protected $table = 'hr_attendance_exceptions';
    protected $guarded = ['id'];
}
