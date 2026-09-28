<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class TicketActionHistory extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_ticket_action_history';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'ticket_id',
        'action_type',
        'reference_type',
        'reference_id',
        'from_status',
        'to_status',
        'reason',
        'amount',
        'action_by',
        'action_at'
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'action_at' => 'datetime'
    ];
}
