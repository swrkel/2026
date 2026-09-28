<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HrHoliday extends Model
{
    protected $table = 'hr_holidays';
    protected $fillable = ['business_id','location_id','holiday_date','name','type','is_paid','notes','is_active','created_by','updated_by'];
}
