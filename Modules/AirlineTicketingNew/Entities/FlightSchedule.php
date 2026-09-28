<?php
namespace Modules\AirlineTicketingNew\Entities;

class FlightSchedule extends BaseAirlineTicketingModel
{
    protected $table = 'atn_flight_schedules';
    protected $guarded = ['id'];
    protected $casts = [
        'departure_at' => 'datetime',
        'arrival_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
