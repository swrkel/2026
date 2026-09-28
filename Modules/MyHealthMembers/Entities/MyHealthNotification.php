<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthNotification extends MyHealthBaseModel
{
    protected $table = 'myhealth_notifications';
    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(MyHealthMember::class, 'member_id');
    }
}
