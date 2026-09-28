<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthClinicalAlert extends Model
{
    protected $table = 'myhealth_clinical_alerts';

    protected $fillable = [
        'business_id',
        'member_id',
        'consultation_id',
        'alert_type',
        'severity',
        'title',
        'message',
        'source',
        'status',
        'acknowledged_by',
        'acknowledged_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(MyHealthMember::class, 'member_id');
    }
}
