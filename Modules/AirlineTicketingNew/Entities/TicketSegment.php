<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class TicketSegment extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_ticket_segments';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'ticket_id',
        'reservation_segment_id',
        'segment_no',
        'airline_id',
        'flight_number',
        'origin_airport_id',
        'destination_airport_id',
        'departure_at',
        'arrival_at',
        'travel_class_id',
        'booking_class',
        'fare_basis',
        'baggage_allowance',
        'coupon_status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'departure_at' => 'datetime',
        'arrival_at' => 'datetime'
    ];
}
