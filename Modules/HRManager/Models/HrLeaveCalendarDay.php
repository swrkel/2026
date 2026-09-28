<?php
namespace Modules\HRManager\Models;
use Illuminate\Database\Eloquent\Model;
class HrLeaveCalendarDay extends Model
{
    protected $table = 'hr_leave_calendar_days';
    protected $guarded = ['id'];
}
