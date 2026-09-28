<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class ReservationPassenger extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_reservation_passengers';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'reservation_id',
        'passenger_id',
        'passenger_type',
        'ticket_name',
        'document_id',
        'seat_preference',
        'meal_preference',
        'special_service_request',
        'status',
        'created_by',
        'updated_by'
    ];
}
