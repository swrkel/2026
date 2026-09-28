<?php
namespace Modules\AirlineTicketingNew\Entities;

class QueueExecution extends BaseAirlineTicketingModel
{
    protected $table = 'atn_queue_executions';
    protected $guarded = ['id'];
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'payload_json' => 'array',
    ];
}
