<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthImmunizationSchedule extends Model
{
    protected $table = 'myhealth_immunization_schedules';

    protected $fillable = [
        'business_id','schedule_name','schedule_type','vaccine_id','dose_no','recommended_age_days','recommended_age_text','interval_days','is_mandatory','status','notes','created_by','updated_by'
    ];
}
