<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HRAttendanceLog extends Model
{
    protected $table = 'hrm_attendance_logs';

    protected $fillable = [
        'business_id','location_id','employee_id','attendance_date','punch_type','punch_time','source',
        'device_uid','ip_address','latitude','longitude','match_score','status','note','created_by'
    ];
}
