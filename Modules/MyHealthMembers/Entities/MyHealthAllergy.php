<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthAllergy extends Model
{
    protected $table = 'myhealth_allergies';

    protected $fillable = [
        'business_id',
        'member_id',
        'allergy_name',
        'allergy_type',
        'severity',
        'reaction',
        'notes',
        'is_active',
    ];
}
