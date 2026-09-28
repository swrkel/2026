<?php
namespace Modules\HRManager\Models;
use Illuminate\Database\Eloquent\Model;
class HrEmployeeRecordAuditLog extends Model
{
    protected $table = 'hr_employee_record_audit_logs';
    protected $guarded = ['id'];
}
