<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeActivityLog extends Model
{
    protected $table = 'hr_employee_activity_logs';
    protected $guarded = ['id'];
}
