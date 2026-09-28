<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HrWeeklyOff extends Model
{
    protected $table = 'hr_weekly_offs';
    protected $fillable = ['business_id','location_id','name','day_of_week','is_active','created_by','updated_by'];
}
