<?php
namespace Modules\HRManager\Models;
use Illuminate\Database\Eloquent\Model;
class HrPayrollAuditLog extends Model
{
    protected $table = 'hr_payroll_audit_logs';
    protected $guarded = ['id'];
}
