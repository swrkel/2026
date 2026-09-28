<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;

class DisnewNotificationPreference extends Model
{
    protected $table = 'disnew_notification_preferences';
    protected $guarded = ['id'];
    protected $casts = ['sms_enabled' => 'boolean', 'email_enabled' => 'boolean', 'in_app_enabled' => 'boolean'];
}
