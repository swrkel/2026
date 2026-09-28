<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class QuotationSegment extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_quotation_segments';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'quotation_id',
        'segment_no',
        'trip_type',
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
        'base_fare',
        'tax_amount',
        'service_fee',
        'discount_amount',
        'segment_total',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'departure_at' => 'datetime',
        'arrival_at' => 'datetime',
        'base_fare' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'service_fee' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'segment_total' => 'decimal:4'
    ];
}
