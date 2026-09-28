<?php
namespace Modules\AirlineTicketingNew\Entities;

class OperationalException extends BaseAirlineTicketingModel
{
    protected $table = 'atn_operational_exceptions';
    protected $guarded = ['id'];
    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'context_json' => 'array',
    ];
}
