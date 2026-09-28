<?php
namespace Modules\HRManager\Models;
use Illuminate\Database\Eloquent\Model;
class HrLeaveApprovalLog extends Model
{
    protected $table = 'hr_leave_approval_logs';
    protected $fillable = ['business_id','leave_request_id','action','from_status','to_status','note','action_by','action_at'];
}
