<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyNotificationLog extends Model
{
    protected $table = 'bs_notification_logs';
    protected $guarded = ['id'];
    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(BeautyNotificationTemplate::class, 'template_id');
    }
}
