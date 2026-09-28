<?php
namespace Modules\AirlineTicketingNew\Entities;

class PortalRequest extends BaseAirlineTicketingModel
{
    protected $table = 'atn_portal_requests';
    protected $guarded = ['id'];
    protected $casts = [
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
        'payload_json' => 'array',
    ];
}
