<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthSetting extends MyHealthBaseModel
{
    protected $table = 'myhealth_settings';

    protected $fillable = [
        'business_id',
        'location_id',
        'setting_group',
        'setting_key',
        'setting_value',
        'value_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
