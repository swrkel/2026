<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyStaff extends Model
{
    protected $table = 'bs_staff';

    protected $fillable = [
        'business_id', 'business_location_id', 'staff_code', 'name', 'mobile', 'email',
        'designation', 'specialization', 'commission_type', 'commission_value',
        'working_hours', 'weekly_off_days', 'status', 'created_by', 'updated_by'
    ];
}
