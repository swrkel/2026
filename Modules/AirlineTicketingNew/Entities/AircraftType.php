<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class AircraftType extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_aircraft_types';
    protected $fillable = ['business_id','business_location_id','store_id','name','iata_code','icao_code','manufacturer','model','seat_capacity','is_active','created_by','updated_by'];
    protected $casts = ['is_active' => 'boolean'];
}
