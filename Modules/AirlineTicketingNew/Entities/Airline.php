<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class Airline extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_airlines';
    protected $fillable = ['business_id','business_location_id','store_id','name','iata_code','icao_code','ticketing_code','country_code','phone','email','website','logo_path','notes','is_active','created_by','updated_by'];
    protected $casts = ['is_active' => 'boolean'];
}
