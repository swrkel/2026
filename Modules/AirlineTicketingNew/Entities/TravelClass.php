<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class TravelClass extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_travel_classes';
    protected $fillable = ['business_id','business_location_id','store_id','name','code','cabin_code','display_order','is_active','created_by','updated_by'];
    protected $casts = ['is_active' => 'boolean'];
}
