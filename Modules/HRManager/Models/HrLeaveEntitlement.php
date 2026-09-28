<?php
namespace Modules\HRManager\Models;
use Illuminate\Database\Eloquent\Model;
class HrLeaveEntitlement extends Model
{
    protected $table = 'hr_leave_entitlements';
    protected $guarded = ['id'];
}
