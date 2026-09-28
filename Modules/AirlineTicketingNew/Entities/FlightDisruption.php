<?php
namespace Modules\AirlineTicketingNew\Entities;

class FlightDisruption extends BaseAirlineTicketingModel
{
    protected $table = 'atn_flight_disruptions';
    protected $guarded = ['id'];
    protected $casts = [
        'reported_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];
}
