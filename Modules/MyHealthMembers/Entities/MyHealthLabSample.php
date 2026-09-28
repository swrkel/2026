<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthLabSample extends Model
{
    protected $table = 'myhealth_lab_samples';

    protected $fillable = [
        'business_id', 'location_id', 'member_id', 'consultation_id', 'lab_request_id',
        'sample_no', 'barcode', 'sample_type', 'priority', 'status', 'collected_at',
        'received_at', 'processed_at', 'verified_at', 'approved_at', 'released_at',
        'collector_id', 'received_by', 'technician_id', 'verified_by', 'approved_by',
        'remarks', 'rejection_reason', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'released_at' => 'datetime',
    ];
}
