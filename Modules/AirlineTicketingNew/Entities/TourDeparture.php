<?php
namespace Modules\AirlineTicketingNew\Entities;

class TourDeparture extends BaseAirlineTicketingModel
{
    protected $table = 'atn_tour_departures';
    protected $guarded = ['id'];
    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'cost_per_person' => 'decimal:4',
        'sale_per_person' => 'decimal:4',
        'is_open' => 'boolean',
    ];
}
