<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthVaccineAdverseEvent extends Model
{
    protected $table = 'myhealth_vaccine_adverse_events';

    protected $fillable = [
        'business_id','location_id','vaccination_record_id','member_id','severity','event_date','symptoms','action_taken','follow_up_required','follow_up_date','reporting_status','reported_to','created_by','updated_by'
    ];

    protected $casts = ['event_date' => 'date', 'follow_up_date' => 'date', 'follow_up_required' => 'boolean'];
}
