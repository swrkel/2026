<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class TicketVoid extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_ticket_voids';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'void_no',
        'ticket_id',
        'request_date',
        'void_date',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'processed_by',
        'remarks',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'request_date' => 'date',
        'void_date' => 'date'
    ];
}
