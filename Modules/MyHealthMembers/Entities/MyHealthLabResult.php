<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthLabResult extends Model
{
    protected $table = 'myhealth_lab_results_enterprise';

    protected $fillable = [
        'business_id', 'location_id', 'sample_id', 'test_id', 'member_id', 'result_value',
        'unit', 'reference_range', 'interpretation', 'is_abnormal', 'is_critical',
        'technician_comments', 'doctor_comments', 'status', 'entered_by', 'verified_by',
        'approved_by', 'entered_at', 'verified_at', 'approved_at', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_abnormal' => 'boolean',
        'is_critical' => 'boolean',
        'entered_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
    ];
}
