<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthSystemHealthCheck extends Model
{
    protected $table = 'myhealth_system_health_checks';

    protected $fillable = [
        'business_id', 'check_key', 'check_name', 'status', 'severity',
        'checked_at', 'message', 'details'
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'details' => 'array',
    ];
}
