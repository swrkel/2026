<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BusinessModuleDateSetting extends Model
{
    protected $table = 'business_module_date_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'module_settings' => 'array',
        'global_date' => 'date:Y-m-d',
    ];
}
