<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class TourBooking extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_tour_bookings';

    protected $fillable = [
        'business_id','business_location_id','store_id','booking_no','tour_package_id',
        'departure_date','return_date','passenger_count','currency_code','gross_amount',
        'discount_amount','net_amount','paid_amount','due_amount','status','remarks',
        'created_by','updated_by'
    ];

    protected $casts = [
        'departure_date' => 'date','return_date' => 'date','gross_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4','net_amount' => 'decimal:4',
        'paid_amount' => 'decimal:4','due_amount' => 'decimal:4',
    ];
}
