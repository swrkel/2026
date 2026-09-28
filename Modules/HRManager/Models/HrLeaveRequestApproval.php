<?php
namespace Modules\HRManager\Models;
use Illuminate\Database\Eloquent\Model;
class HrLeaveRequestApproval extends Model
{
    protected $table = 'hr_leave_request_approvals';
    protected $guarded = ['id'];
}
