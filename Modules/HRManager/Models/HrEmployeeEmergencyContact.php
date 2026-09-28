<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeEmergencyContact extends Model
{
    protected $table = 'hr_employee_emergency_contacts';
    protected $fillable = ['employee_id','contact_name','relationship','mobile','phone','address','notes'];
}
