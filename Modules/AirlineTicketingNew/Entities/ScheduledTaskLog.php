<?php
namespace Modules\AirlineTicketingNew\Entities;

class ScheduledTaskLog extends BaseAirlineTicketingModel
{
    protected $table = 'atn_scheduled_task_logs';
    protected $guarded = ['id'];
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'context_json' => 'array',
    ];
}
