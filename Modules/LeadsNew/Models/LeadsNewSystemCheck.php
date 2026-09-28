<?php

namespace Modules\LeadsNew\Models;

use Illuminate\Database\Eloquent\Model;

class LeadsNewSystemCheck extends Model
{
    protected $table = 'leads_new_system_checks';

    protected $guarded = ['id'];

    protected $casts = [
        'details' => 'array',
        'checked_at' => 'datetime',
    ];
}
