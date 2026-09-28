<?php
namespace Modules\HRManager\Models;
use Illuminate\Database\Eloquent\Model;
class HrLeaveRequestDay extends Model
{
    protected $table = 'hr_leave_request_days';
    protected $fillable = ['business_id','leave_request_id','employee_id','leave_date','day_value','day_type','status'];
}
