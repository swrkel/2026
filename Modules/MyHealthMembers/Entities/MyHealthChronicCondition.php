<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthChronicCondition extends Model
{
    protected $table = 'myhealth_chronic_conditions';

    protected $fillable = [
        'business_id',
        'member_id',
        'condition_name',
        'diagnosed_date',
        'severity',
        'status',
        'notes',
        'is_active',
    ];
}
