<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthNotificationTemplate extends MyHealthBaseModel
{
    protected $table = 'myhealth_notification_templates';
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
