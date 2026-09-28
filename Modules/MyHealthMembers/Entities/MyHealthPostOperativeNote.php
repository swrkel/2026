<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthPostOperativeNote extends Model
{
    protected $table = 'myhealth_post_operative_notes';

    protected $fillable = [
        'business_id', 'surgery_schedule_id', 'member_id', 'operative_record_id',
        'recovery_status', 'pain_score', 'vital_status', 'icu_transfer_required',
        'ward_transfer_required', 'post_op_instructions', 'medications',
        'follow_up_plan', 'discharge_recommendations', 'noted_by', 'noted_at',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'icu_transfer_required' => 'boolean',
        'ward_transfer_required' => 'boolean',
        'noted_at' => 'datetime',
    ];
}
