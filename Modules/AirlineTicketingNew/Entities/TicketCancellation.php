<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class TicketCancellation extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_ticket_cancellations';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'cancellation_no',
        'ticket_id',
        'request_date',
        'cancellation_date',
        'reason',
        'cancellation_fee',
        'refundable_amount',
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
        'cancellation_date' => 'date',
        'cancellation_fee' => 'decimal:4',
        'refundable_amount' => 'decimal:4'
    ];
}
