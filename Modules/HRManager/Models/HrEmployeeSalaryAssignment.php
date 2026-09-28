<?php
namespace Modules\HRManager\Models;
use Illuminate\Database\Eloquent\Model;
class HrEmployeeSalaryAssignment extends Model
{
    protected $table = 'hr_employee_salary_assignments';
    protected $guarded = ['id'];
}
