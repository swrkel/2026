<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringEmployeeProductivity extends Model
{
    protected $table = 'tailoring_employee_productivities';
    protected $guarded = ['id'];
    protected $casts = ['work_date'=>'date'];
}
