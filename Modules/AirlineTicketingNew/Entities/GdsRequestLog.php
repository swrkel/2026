<?php
namespace Modules\AirlineTicketingNew\Entities;

class GdsRequestLog extends BaseAirlineTicketingModel
{
    protected $table = 'atn_gds_request_logs';
    protected $guarded = ['id'];
    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
