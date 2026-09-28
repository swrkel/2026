<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HrAttendanceDailySummary extends Model
{
    protected $table = 'hr_attendance_daily_summaries';
    protected $guarded = ['id'];
}
