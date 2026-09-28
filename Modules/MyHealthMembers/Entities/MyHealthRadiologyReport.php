<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthRadiologyReport extends Model
{
    protected $table = 'myhealth_radiology_reports';

    protected $fillable = [
        'business_id', 'radiology_request_id', 'member_id', 'report_no', 'findings',
        'impression', 'recommendations', 'critical_finding', 'critical_notes', 'status',
        'reported_by', 'verified_by', 'approved_by', 'released_by', 'reported_at',
        'verified_at', 'approved_at', 'released_at', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'critical_finding' => 'boolean',
        'reported_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(MyHealthRadiologyRequest::class, 'radiology_request_id');
    }
}
