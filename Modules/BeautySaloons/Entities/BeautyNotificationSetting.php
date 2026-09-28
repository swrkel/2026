<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyNotificationSetting extends Model
{
    protected $table = 'bs_notification_settings';
    protected $guarded = ['id'];
    protected $casts = [
        'sms_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'gateway_config' => 'array',
    ];
}
