<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class Currency extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_currencies';
    protected $fillable = ['business_id','business_location_id','store_id','code','name','symbol','decimal_places','exchange_rate','is_base','is_active','created_by','updated_by'];
    protected $casts = ['is_active' => 'boolean', 'is_base' => 'boolean', 'exchange_rate' => 'decimal:8'];
}
