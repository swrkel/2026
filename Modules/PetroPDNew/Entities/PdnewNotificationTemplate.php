<?php

namespace Modules\PetroPDNew\Entities;

class PdnewNotificationTemplate extends PdnewBaseModel
{
    protected $table = 'pdnew_notification_templates';
    protected $casts = [
        'channels' => 'array',
        'is_active' => 'boolean',
    ];
}
