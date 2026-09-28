<?php

namespace Modules\PetroPDNew\Entities;

class PdnewNotificationLog extends PdnewBaseModel
{
    protected $table = 'pdnew_notification_logs';
    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
