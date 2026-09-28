<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthSurgeryChecklist extends Model
{
    protected $table = 'myhealth_surgery_checklists';

    protected $fillable = [
        'business_id', 'surgery_schedule_id', 'member_id', 'consent_verified',
        'identity_verified', 'procedure_site_marked', 'allergy_checked',
        'investigations_completed', 'blood_available', 'anaesthesia_clearance',
        'fasting_confirmed', 'equipment_ready', 'implant_available', 'antibiotic_given',
        'checklist_status', 'checked_by', 'checked_at', 'remarks', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'consent_verified' => 'boolean',
        'identity_verified' => 'boolean',
        'procedure_site_marked' => 'boolean',
        'allergy_checked' => 'boolean',
        'investigations_completed' => 'boolean',
        'blood_available' => 'boolean',
        'anaesthesia_clearance' => 'boolean',
        'fasting_confirmed' => 'boolean',
        'equipment_ready' => 'boolean',
        'implant_available' => 'boolean',
        'antibiotic_given' => 'boolean',
        'checked_at' => 'datetime',
    ];
}
