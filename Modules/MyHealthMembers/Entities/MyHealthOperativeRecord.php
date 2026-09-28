<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthOperativeRecord extends Model
{
    protected $table = 'myhealth_operative_records';

    protected $fillable = [
        'business_id', 'surgery_schedule_id', 'member_id', 'operation_no',
        'anaesthesia_type', 'procedure_performed', 'incision_time', 'closure_time',
        'findings', 'procedure_notes', 'implants_used', 'consumables_used',
        'blood_loss_ml', 'blood_transfusion', 'complications', 'specimen_sent',
        'surgeon_id', 'anaesthetist_id', 'scrub_nurse_id', 'circulating_nurse_id',
        'status', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'incision_time' => 'datetime',
        'closure_time' => 'datetime',
        'blood_transfusion' => 'boolean',
        'specimen_sent' => 'boolean',
    ];
}
