<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthVaccinationRecord extends Model
{
    protected $table = 'myhealth_vaccination_records';

    protected $fillable = [
        'business_id','location_id','member_id','vaccine_id','batch_id','vaccination_no','dose_no','date_given','next_due_date','administered_by','administered_location','status','adverse_reaction','reaction_notes','certificate_no','certificate_issued_at','remarks','created_by','updated_by'
    ];

    protected $casts = ['date_given' => 'date', 'next_due_date' => 'date', 'certificate_issued_at' => 'datetime'];
}
