<?php
namespace Modules\HRManager\Models;
use Illuminate\Database\Eloquent\Model;
class HrPayslip extends Model
{
    protected $table = 'hr_payslips';
    protected $guarded = ['id'];
}
