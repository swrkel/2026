<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthRecoveryTest extends Model
{
    protected $table = 'myhealth_recovery_tests';

    protected $fillable = [
        'business_id', 'test_no', 'test_type', 'status', 'tested_at',
        'tested_by', 'duration_seconds', 'result_summary', 'issues_found', 'recommendations'
    ];

    protected $casts = [
        'tested_at' => 'datetime',
    ];
}
