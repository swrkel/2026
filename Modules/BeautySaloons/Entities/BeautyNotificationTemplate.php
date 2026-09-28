<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyNotificationTemplate extends Model
{
    protected $table = 'bs_notification_templates';
    protected $guarded = ['id'];
    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];
}
