<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class Route extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_routes';
    protected $fillable = ['business_id','business_location_id','store_id','code','origin_airport_id','destination_airport_id','default_airline_id','distance_km','duration_minutes','is_active','created_by','updated_by'];
    protected $casts = ['is_active' => 'boolean'];
}
