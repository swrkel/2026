<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeFamilyMember extends Model
{
    protected $table = 'hr_employee_family_members';
    protected $guarded = ['id'];
}
