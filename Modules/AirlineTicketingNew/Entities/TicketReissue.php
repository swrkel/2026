<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class TicketReissue extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_ticket_reissues';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'reissue_no',
        'original_ticket_id',
        'new_ticket_id',
        'reservation_id',
        'request_date',
        'processed_date',
        'reason',
        'fare_difference',
        'tax_difference',
        'service_fee',
        'penalty_amount',
        'total_collectable',
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
        'processed_date' => 'date',
        'fare_difference' => 'decimal:4',
        'tax_difference' => 'decimal:4',
        'service_fee' => 'decimal:4',
        'penalty_amount' => 'decimal:4',
        'total_collectable' => 'decimal:4'
    ];
}
