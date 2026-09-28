<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class Hotel extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_hotels';

    protected $fillable = [
        'business_id','business_location_id','store_id','hotel_code','name','country_code',
        'city','address','phone','email','star_rating','supplier_id','is_active',
        'created_by','updated_by'
    ];

    protected $casts = ['is_active' => 'boolean'];
}
