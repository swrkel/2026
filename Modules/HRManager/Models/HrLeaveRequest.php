<?php
namespace Modules\HRManager\Models;
use Illuminate\Database\Eloquent\Model;
class HrLeaveRequest extends Model
{
    protected $table = 'hr_leave_requests';
    protected $guarded = ['id'];
}
