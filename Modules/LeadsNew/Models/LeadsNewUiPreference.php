<?php

namespace Modules\LeadsNew\Models;

use Illuminate\Database\Eloquent\Model;

class LeadsNewUiPreference extends Model
{
    protected $table = 'leads_new_ui_preferences';

    protected $guarded = ['id'];

    protected $casts = [
        'preferences' => 'array',
    ];
}
