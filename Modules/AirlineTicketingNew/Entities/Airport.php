<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class Airport extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_airports';
    protected $fillable = ['business_id','business_location_id','store_id','name','iata_code','icao_code','country_code','city','timezone','latitude','longitude','terminal_notes','is_active','created_by','updated_by'];
    protected $casts = ['is_active' => 'boolean'];
}
