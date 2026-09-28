<?php
namespace Modules\AirlineTicketingNew\Entities;

class HealthIncident extends BaseAirlineTicketingModel
{
    protected $table = 'atn_health_incidents';
    protected $guarded = ['id'];
    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'context_json' => 'array',
    ];
}
